<?php

declare(strict_types=1);

namespace Phptomb\Tests\E2E;

use DB;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests against MySQL (run inside Docker: see docker/e2e.sh).
 */
final class PhptombE2ETest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::resetConnection();
        self::clearTables();
    }

    protected function tearDown(): void
    {
        self::clearTables();
        DB::resetConnection();
        parent::tearDown();
    }

    private static function clearTables(): void
    {
        DB::raw('SET FOREIGN_KEY_CHECKS=0');
        DB::raw('DELETE FROM posts');
        DB::raw('DELETE FROM users');
        DB::raw('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testInsertSelectFirstGetLastInsertId(): void
    {
        $ok = DB::table('users')->insert([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'role' => 'admin',
        ]);
        $this->assertTrue($ok);
        $this->assertGreaterThan(0, DB::getLastInsertId());

        $first = DB::table('users')->where('email', 'ada@example.com')->first();
        $this->assertNotNull($first);
        $this->assertSame('Ada', $first->name);

        $rows = DB::table('users')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('ada@example.com', $rows[0]->email);
    }

    public function testWhereOperatorsAndUpdate(): void
    {
        DB::table('users')->insert(['name' => 'A', 'email' => 'a@x.com', 'role' => 'user']);
        DB::table('users')->insert(['name' => 'B', 'email' => 'b@x.com', 'role' => 'user']);

        $gt = DB::table('users')->where('id', '>', '0')->orderBy('id', 'ASC')->get();
        $this->assertCount(2, $gt);

        $one = DB::table('users')->where('name', 'A')->first();
        $this->assertNotNull($one);

        $updated = DB::table('users')->where('name', 'A')->update(['role' => 'editor']);
        $this->assertTrue($updated);

        $row = DB::table('users')->where('name', 'A')->first();
        $this->assertNotNull($row);
        $this->assertSame('editor', $row->role);
    }

    public function testWhereInWhereNotInOrWhere(): void
    {
        DB::table('users')->insert(['name' => 'u1', 'email' => 'u1@x.com', 'role' => 'a']);
        DB::table('users')->insert(['name' => 'u2', 'email' => 'u2@x.com', 'role' => 'b']);
        DB::table('users')->insert(['name' => 'u3', 'email' => 'u3@x.com', 'role' => 'c']);

        $in = DB::table('users')->whereIn('role', ['a', 'b'])->orderBy('id', 'ASC')->get();
        $this->assertCount(2, $in);

        $notIn = DB::table('users')->whereNotIn('role', ['a', 'b'])->get();
        $this->assertCount(1, $notIn);

        $or = DB::table('users')->where('role', 'a')->orWhere('role', 'c')->get();
        $this->assertCount(2, $or);
    }

    public function testCountLimitExistsDistinct(): void
    {
        DB::table('users')->insert(['name' => 'n1', 'email' => 'n1@x.com', 'role' => 'u']);
        DB::table('users')->insert(['name' => 'n2', 'email' => 'n2@x.com', 'role' => 'u']);

        $this->assertSame(2, DB::table('users')->count());

        $limited = DB::table('users')->orderBy('id', 'ASC')->limit(0, 1)->get();
        $this->assertCount(1, $limited);

        $this->assertTrue(DB::table('users')->where('name', 'n1')->exists());
        $this->assertFalse(DB::table('users')->where('name', 'no-such')->exists());

        $d = DB::table('users')->distinct()->select('role')->get();
        $this->assertCount(1, $d);
    }

    public function testDeleteRequiresWhere(): void
    {
        DB::table('users')->insert(['name' => 'x', 'email' => 'x@x.com', 'role' => 'u']);
        $this->assertFalse(DB::table('users')->delete());
        $this->assertTrue(DB::table('users')->where('email', 'x@x.com')->delete());
        $this->assertSame(0, DB::table('users')->count());
    }

    public function testLeftJoin(): void
    {
        DB::table('users')->insert(['name' => 'Author', 'email' => 'au@x.com', 'role' => 'u']);
        $uid = DB::getLastInsertId();
        DB::table('posts')->insert(['user_id' => $uid, 'title' => 'Hello']);

        $rows = DB::table('users')
            ->leftJoin('posts', 'users.id', 'posts.user_id')
            ->select('users.name, posts.title')
            ->orderBy('users.id', 'ASC')
            ->get();

        $this->assertNotEmpty($rows);
        $this->assertSame('Hello', $rows[0]->title);
    }

    public function testTransactionRollbackAndCommit(): void
    {
        $this->assertTrue(DB::beginTransaction());
        DB::table('users')->insert(['name' => 't1', 'email' => 't1@x.com', 'role' => 'u']);
        $this->assertTrue(DB::rollback());

        $this->assertSame(0, DB::table('users')->count());

        $this->assertTrue(DB::beginTransaction());
        DB::table('users')->insert(['name' => 't2', 'email' => 't2@x.com', 'role' => 'u']);
        $this->assertTrue(DB::commit());

        $this->assertSame(1, DB::table('users')->count());
    }

    public function testRawQuery(): void
    {
        DB::table('users')->insert(['name' => 'r', 'email' => 'r@x.com', 'role' => 'u']);
        $res = DB::raw('SELECT COUNT(*) AS c FROM users');
        $this->assertNotFalse($res);
        $this->assertInstanceOf(\mysqli_result::class, $res);
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        $this->assertSame('1', $row['c']);
    }

    public function testWhereNullAndNotNull(): void
    {
        DB::table('users')->insert(['name' => 'nn', 'email' => 'nn@x.com', 'role' => 'u']);

        $nulls = DB::table('users')->whereNull('nickname')->get();
        $this->assertGreaterThanOrEqual(1, count($nulls));

        DB::table('users')->where('email', 'nn@x.com')->update(['nickname' => 'N']);
        $notNull = DB::table('users')->whereNotNull('nickname')->get();
        $this->assertNotEmpty($notNull);
    }
}
