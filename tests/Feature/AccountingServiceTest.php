<?php

namespace Tests\Feature;

use App\Article;
use App\Comment;
use App\Invoice;
use App\Notifications\BalanceWarningNotification;
use App\Services\AccountingService;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingServiceTest extends TestCase
{

    /**
     * @var AccountingService
     */
    protected $accountingService;

    public function setUp()
    {
        parent::setUp();
        User::where("username", "testuser")->delete();

        /** @var AccountingService $accountingService */
        $this->accountingService = app(AccountingService::class);
    }

    public function testUserShouldGet100kTomanAfterRegistration()
    {
        $response = $this->post("/api/users", [
            "user" => [
                "username" => "testuser",
                "email"    => "test@test.com",
                "password" => "test1234",
            ],
        ], [
            "Accept" => "application/json",
        ]);

        $user = $this->getUser();

        $this->assertEquals(100000, $this->accountingService->userBalance($user->id));
    }

    public function testUserCanChargeHisWallet()
    {
        $user           = factory(User::class)->create();
        $initialBalance = $this->accountingService->userBalance($user->id);
        $this->accountingService->chargeWallet($user->id, 10000);
        $currentBalance = $this->accountingService->userBalance($user->id);
        $this->assertDatabaseHas("transactions", [
            "user_id" => $user->id,
            "credit"  => 10000,
        ]);
        $this->assertEquals(10000, $currentBalance - $initialBalance);
    }

    public function testUserShouldPay5kForEachArticleSubmission()
    {
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();
        $initialBalance = $this->accountingService->userBalance($user->id);

        $response = $this->post("/api/articles", [
            "article" => [
                "title"       => "my first article",
                "description" => " Lorem ipsum dolor sit amet.",
                "body"        => " Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
            ],
        ], [
            "Accept"        => "application/json",
            "Authorization" => "Bearer $token",
        ]);

        $currentBalance = $this->accountingService->userBalance($user->id);

        $this->assertEquals(5000, $initialBalance - $currentBalance);
    }

    public function testUserShouldComment5timesForFreeAndThenWithCharge()
    {
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();


        for ($i = 1; $i<=5; $i++){
            $initialBalance = $this->accountingService->userBalance($user->id);
            $article = factory(Article::class)->create(["user_id" => $user->id]);
            $res =$this->submitComment($article, $token);
            $currentBalance = $this->accountingService->userBalance($user->id);
            $this->assertEquals(0, $initialBalance - $currentBalance);
        }

        $initialBalance = $this->accountingService->userBalance($user->id);
        $article = factory(Article::class)->create(["user_id" => $user->id]);
        $this->submitComment($article, $token);
        $currentBalance = $this->accountingService->userBalance($user->id);
        $this->assertEquals(5000, $initialBalance - $currentBalance);
    }

    public function testUserShouldReceiveNotificationWhenBalanceIsLessThan20000()
    {
        Notification::fake();
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();

        $initial = $this->accountingService->userBalance($user->id);
        $this->accountingService->deductFromWallet($user->id, $initial- 15000);

        Notification::assertSentTo([$user], BalanceWarningNotification::class);
    }

    public function testUserShouldNotReceiveNotificationWhenBalanceIsHigherThan20000()
    {
        Notification::fake();
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();

        $initial = $this->accountingService->userBalance($user->id);
        $this->accountingService->deductFromWallet($user->id, $initial- 30000);

        Notification::assertNotSentTo([$user], BalanceWarningNotification::class);
    }

    public function testUserCanNotCreateArticleOrCommentWhenHeIsInactive()
    {
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();
        $user->deactivate();
        $response = $this->post("/api/articles", [
            "article" => [
                "title"       => "my first article",
                "description" => " Lorem ipsum dolor sit amet.",
                "body"        => " Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
            ],
        ], [
            "Accept"        => "application/json",
            "Authorization" => "Bearer $token",
        ]);

        $response->assertStatus(403);

        $article = factory(Article::class)->create(["user_id" => $user->id]);
        $response = $this->submitComment($article, $token);

        $response->assertStatus(403);
    }

    public function testInactiveUsersAfter24HoursCanBeDeletedWithCommandLine()
    {
        $user = factory(User::class)->create([
            "status" => false,
            "status_changed_at" => now()->subHours(26)
        ]);

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "status" => false
        ]);

        $this->artisan("delete:inactive-users");

        $this->assertDatabaseMissing("users", [
            "id" => $user->id,
            "status" => false
        ]);
    }

    public function testInactiveUsersBefore24HoursMustNotBeDeletedWithCommandLine()
    {
        $user = factory(User::class)->create([
            "status" => false,
            "status_changed_at" => now()->subHours(23)
        ]);

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "status" => false
        ]);

        $this->artisan("delete:inactive-users");

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "status" => false
        ]);
    }

    public function testAnInvoiceShouldBeIssuedForEachTransactionThatAUserHasForArticles()
    {
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();
        Invoice::where("user_id", $user->id)->delete();
        $response = $this->post("/api/articles", [
            "article" => [
                "title"       => "my first article",
                "description" => " Lorem ipsum dolor sit amet.",
                "body"        => " Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
            ],
        ], [
            "Accept"        => "application/json",
            "Authorization" => "Bearer $token",
        ]);
        $this->assertDatabaseHas("invoices", [
            "user_id" => $user->id,
            "amount" => 5000,
            "reason_type" => Article::class,
        ]);
    }

    public function testAnInvoiceShouldBeIssuedForEachTransactionThatAUserHasForComment()
    {
        $response       = $this->registerUser();
        $token          = $this->token($response);
        $user           = $this->getUser();
        Invoice::where("user_id", $user->id)->delete();
        $article = factory(Article::class)->create(["user_id" => $user->id]);
        factory(Comment::class, 5)->create(["article_id" => $article->id, "user_id" => $user->id]);
        $this->assertDatabaseMissing("invoices", [
            "user_id" => $user->id,
            "amount" => 5000,
            "reason_type" => Comment::class,
        ]);
        $this->submitComment($article, $token);
        $this->assertDatabaseHas("invoices", [
            "user_id" => $user->id,
            "amount" => 5000,
            "reason_type" => Comment::class,
        ]);
    }

    /**
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function registerUser(): \Illuminate\Foundation\Testing\TestResponse
    {
        return $this->post("/api/users", [
            "user" => [
                "username" => "testuser",
                "email"    => "test@test.com",
                "password" => "test1234",
            ],
        ], [
            "Accept" => "application/json",
        ]);
    }

    /**
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     *
     * @return mixed
     */
    protected function token(\Illuminate\Foundation\Testing\TestResponse $response)
    {
        $arr = $response->json();

        return ($arr["user"]["token"]);
    }

    /**
     * @return mixed
     */
    protected function getUser()
    {
        $user = User::where("username", "testuser")->first();

        return $user;
    }

    /**
     * @param $article
     * @param $token
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function submitComment($article, $token)
    {
        return $this->post("/api/articles/" . $article->slug . "/comments", [
            "comment" => [
                "body" => " Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
            ],
        ], [
            "Accept"        => "application/json",
            "Authorization" => "Bearer $token",
        ]);
    }
}
