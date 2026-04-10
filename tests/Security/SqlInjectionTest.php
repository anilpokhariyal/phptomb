<?php

declare(strict_types=1);

namespace Phptomb\Tests\Security;

use DB;
use PHPUnit\Framework\TestCase;

/**
 * Security-focused tests (MySQL). Run with the same stack as E2E: ./docker/e2e.sh
 *
 * These tests assert that typical query-builder paths parameterize/escape values so
 * classic injection payloads remain data, not executable SQL. APIs that intentionally
 * embed raw SQL (e.g. having(), raw()) require trusted input — see individual tests.
 */
final class SqlInjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::resetConnection();
        $this->clearTables();
    }

    protected function tearDown(): void
    {
        $this->clearTables();
        DB::resetConnection();
        parent::tearDown();
    }

    private function clearTables(): void
    {
        DB::raw('SET FOREIGN_KEY_CHECKS=0');
        DB::raw('DELETE FROM posts');
        DB::raw('DELETE FROM users');
        DB::raw('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testWhereValueIsEscapedAndDoesNotBypassWithOrOneEqualsOne(): void
    {
        DB::table('users')->insert([
            'name' => 'Victim',
            'email' => 'victim@example.com',
            'role' => 'user',
        ]);

        $attack = "anything@x.com' OR '1'='1";
        $rows = DB::table('users')->where('email', $attack)->get();
        $this->assertCount(0, $rows, 'OR 1=1 in value must not match all rows');

        DB::table('users')->insert([
            'name' => 'Attacker row',
            'email' => $attack,
            'role' => 'user',
        ]);

        $rows = DB::table('users')->where('email', $attack)->get();
        $this->assertCount(1, $rows);
        $this->assertSame($attack, $rows[0]->email);
    }

    public function testInsertStringCannotAppendSecondStatement(): void
    {
        $payload = "pwned@x.com'; DELETE FROM users; --";
        DB::table('users')->insert([
            'name' => 'N',
            'email' => $payload,
            'role' => 'user',
        ]);

        $this->assertSame(1, DB::table('users')->count());
        $row = DB::table('users')->where('email', $payload)->first();
        $this->assertNotNull($row);
        $this->assertSame($payload, $row->email);
    }

    public function testUpdateValueCannotTruncateTableViaPayload(): void
    {
        DB::table('users')->insert([
            'name' => 'Keep',
            'email' => 'keep@x.com',
            'role' => 'user',
        ]);
        $id = DB::getLastInsertId();

        $malicious = "x', role='hacked";
        $ok = DB::table('users')->where('id', (string) $id)->update(['email' => $malicious]);
        $this->assertTrue($ok);

        $this->assertSame(1, DB::table('users')->count());
        $row = DB::table('users')->where('id', (string) $id)->first();
        $this->assertNotNull($row);
        $this->assertSame($malicious, $row->email);
    }

    public function testWhereInEscapesStringPayloads(): void
    {
        DB::table('users')->insert([
            'name' => 'a',
            'email' => 'a@x.com',
            'role' => 'r1',
        ]);
        DB::table('users')->insert([
            'name' => 'b',
            'email' => 'b@x.com',
            'role' => 'r2',
        ]);

        $evil = "r1' OR '1'='1";
        $rows = DB::table('users')->whereIn('role', ['r1', $evil])->orderBy('id', 'ASC')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('a@x.com', $rows[0]->email);
    }

    public function testColumnNameInjectionIsStrippedToIdentifiers(): void
    {
        DB::table('users')->insert([
            'name' => 'U',
            'email' => 'u@x.com',
            'role' => 'user',
        ]);

        // Malicious "column" becomes a sanitized identifier (e.g. `emailOR11`), not valid SQL logic.
        // With strict mysqli, unknown columns surface as an exception rather than silent [].
        $this->expectException(\mysqli_sql_exception::class);
        DB::table('users')->where('email OR 1=1', 'u@x.com')->get();
    }

    public function testLikeWhereEscapesPercentAndQuoteInValue(): void
    {
        DB::table('users')->insert([
            'name' => 'L',
            'email' => 'like@test.com',
            'role' => 'user',
        ]);

        $needle = "test%' OR '1'='1";
        $rows = DB::table('users')->where('email', 'LIKE', $needle)->get();
        $this->assertCount(0, $rows);
    }

    public function testUsersTableStillExistsAfterMaliciousInsertAndSelect(): void
    {
        DB::table('users')->insert([
            'name' => 'X',
            'email' => "y@y.com') OR 1=1-- ",
            'role' => 'user',
        ]);
        $this->assertGreaterThan(0, DB::table('users')->count());
        $check = DB::raw('SHOW TABLES LIKE \'users\'');
        $this->assertNotFalse($check);
        $this->assertInstanceOf(\mysqli_result::class, $check);
        $this->assertSame(1, mysqli_num_rows($check));
        mysqli_free_result($check);
    }

    public function testHavingAcceptsRawSqlCallerMustValidateInput(): void
    {
        DB::table('users')->insert([
            'name' => 'A',
            'email' => 'a@x.com',
            'role' => 'u',
        ]);
        DB::table('users')->insert([
            'name' => 'B',
            'email' => 'b@x.com',
            'role' => 'u',
        ]);

        // Documented behavior: having() concatenates SQL — only use with trusted fragments.
        $count = DB::table('users')
            ->select('role')
            ->groupBy('role')
            ->having('COUNT(*) >= 1')
            ->get();
        $this->assertNotEmpty($count);
    }
}
