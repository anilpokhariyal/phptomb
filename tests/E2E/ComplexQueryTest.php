<?php

declare(strict_types=1);

namespace Phptomb\Tests\E2E;

use DB;
use PHPUnit\Framework\TestCase;

/**
 * Complex query combinations: multiple WHERE, GROUP BY, HAVING, ORDER BY.
 */
final class ComplexQueryTest extends TestCase
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

    public function testMultipleWhereClausesChainWithAnd(): void
    {
        DB::table('users')->insert(['name' => 'Alice', 'email' => 'a@x.com', 'role' => 'staff']);
        DB::table('users')->insert(['name' => 'Bob', 'email' => 'b@x.com', 'role' => 'staff']);
        DB::table('users')->insert(['name' => 'Carol', 'email' => 'c@x.com', 'role' => 'admin']);

        $rows = DB::table('users')
            ->where('role', 'staff')
            ->where('name', 'Bob')
            ->orderBy('id', 'ASC')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Bob', $rows[0]->name);
        $this->assertSame('b@x.com', $rows[0]->email);
    }

    public function testMultipleWhereWithComparisonAndOrdering(): void
    {
        DB::table('users')->insert(['name' => 'E1', 'email' => 'e1@x.com', 'role' => 'u']);
        DB::table('users')->insert(['name' => 'E2', 'email' => 'e2@x.com', 'role' => 'u']);
        DB::table('users')->insert(['name' => 'E3', 'email' => 'e3@x.com', 'role' => 'v']);

        $rows = DB::table('users')
            ->where('role', 'u')
            ->where('id', '>', '0')
            ->orderBy('name', 'DESC')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame('E2', $rows[0]->name);
        $this->assertSame('E1', $rows[1]->name);
    }

    public function testWhereWithOrWhereAndSort(): void
    {
        DB::table('users')->insert(['name' => 'A', 'email' => 'a1@x.com', 'role' => 'r1']);
        DB::table('users')->insert(['name' => 'B', 'email' => 'b1@x.com', 'role' => 'r2']);
        DB::table('users')->insert(['name' => 'C', 'email' => 'c1@x.com', 'role' => 'r3']);

        $rows = DB::table('users')
            ->where('role', 'r1')
            ->orWhere('role', 'r3')
            ->orderBy('name', 'ASC')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame('A', $rows[0]->name);
        $this->assertSame('C', $rows[1]->name);
    }

    public function testMultipleOrderByColumns(): void
    {
        DB::table('users')->insert(['name' => 'Zed', 'email' => 'z1@x.com', 'role' => 'g']);
        DB::table('users')->insert(['name' => 'Amy', 'email' => 'a2@x.com', 'role' => 'g']);
        DB::table('users')->insert(['name' => 'Amy', 'email' => 'a1@x.com', 'role' => 'h']);

        $rows = DB::table('users')
            ->orderBy('name', 'ASC')
            ->orderBy('email', 'ASC')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertSame('a1@x.com', $rows[0]->email);
        $this->assertSame('a2@x.com', $rows[1]->email);
        $this->assertSame('z1@x.com', $rows[2]->email);
    }

    public function testGroupByHavingAndOrderBy(): void
    {
        DB::table('users')->insert(['name' => 'S1', 'email' => 's1@x.com', 'role' => 'staff']);
        DB::table('users')->insert(['name' => 'S2', 'email' => 's2@x.com', 'role' => 'staff']);
        DB::table('users')->insert(['name' => 'A1', 'email' => 'a@x.com', 'role' => 'admin']);

        $rows = DB::table('users')
            ->select('role, COUNT(*) AS cnt')
            ->groupBy('role')
            ->having('COUNT(*) >= 2')
            ->orderBy('role', 'ASC')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('staff', $rows[0]->role);
        $this->assertSame('2', (string) $rows[0]->cnt);
    }

    public function testWhereGroupByHavingOrderByCombined(): void
    {
        DB::table('users')->insert(['name' => 'P1', 'email' => 'p1@x.com', 'role' => 'x']);
        DB::table('users')->insert(['name' => 'P2', 'email' => 'p2@x.com', 'role' => 'x']);
        DB::table('users')->insert(['name' => 'P3', 'email' => 'p3@x.com', 'role' => 'y']);
        DB::table('users')->insert(['name' => 'Skip', 'email' => 'skip@x.com', 'role' => 'x']);

        $rows = DB::table('users')
            ->where('name', '!=', 'Skip')
            ->select('role, COUNT(*) AS cnt')
            ->groupBy('role')
            ->having('COUNT(*) >= 2')
            ->orderBy('cnt', 'DESC')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('x', $rows[0]->role);
        $this->assertSame('2', (string) $rows[0]->cnt);
    }

    public function testCountWithGroupByAndHaving(): void
    {
        DB::table('users')->insert(['name' => 'c1', 'email' => 'c1@x.com', 'role' => 'g']);
        DB::table('users')->insert(['name' => 'c2', 'email' => 'c2@x.com', 'role' => 'g']);
        DB::table('users')->insert(['name' => 'c3', 'email' => 'c3@x.com', 'role' => 'h']);

        $n = DB::table('users')
            ->select('role')
            ->groupBy('role')
            ->having('COUNT(*) = 2')
            ->count();

        $this->assertSame(1, $n);
    }

    public function testWhereLimitAndOrder(): void
    {
        DB::table('users')->insert(['name' => 'L1', 'email' => 'l1@x.com', 'role' => 'z']);
        DB::table('users')->insert(['name' => 'L2', 'email' => 'l2@x.com', 'role' => 'z']);
        DB::table('users')->insert(['name' => 'L3', 'email' => 'l3@x.com', 'role' => 'z']);

        $rows = DB::table('users')
            ->where('role', 'z')
            ->orderBy('name', 'ASC')
            ->limit(1, 2)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame('L2', $rows[0]->name);
        $this->assertSame('L3', $rows[1]->name);
    }
}
