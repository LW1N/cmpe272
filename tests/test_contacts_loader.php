<?php
declare(strict_types=1);

function run_contacts_loader_tests(TestRunner $t): void
{
    $t->section('Contacts Loader');

    require_once PROJECT_ROOT . '/includes/contacts_loader.php';

    $fixturesDir = __DIR__ . '/fixtures/contacts';

    $t->run('loads contacts from production CSV', function () use ($t) {
        $contacts = load_contacts_from_files(PROJECT_ROOT . '/data/contacts');
        $t->assertNotEmpty($contacts, 'Should load contacts from data/contacts/');
        $t->assertCount(5, $contacts, 'contacts.csv has 5 entries');
        $t->assertEqual('Support', $contacts[0]['name']);
        $t->assertEqual('support@passandplay.com', $contacts[0]['email']);
    });

    $t->run('each contact has all expected keys', function () use ($t) {
        $contacts = load_contacts_from_files(PROJECT_ROOT . '/data/contacts');
        $expectedKeys = ['name', 'role', 'email', 'phone', 'discord'];
        foreach ($contacts as $i => $contact) {
            foreach ($expectedKeys as $key) {
                $t->assertTrue(
                    array_key_exists($key, $contact),
                    "Contact #{$i} missing key '{$key}'"
                );
            }
        }
    });

    $t->run('returns empty array for nonexistent directory', function () use ($t) {
        $contacts = load_contacts_from_files('/tmp/nonexistent_dir_' . uniqid());
        $t->assertEmpty($contacts);
    });

    $t->run('returns empty array for directory with no CSV files', function () use ($t) {
        $tmpDir = sys_get_temp_dir() . '/empty_contacts_' . uniqid();
        mkdir($tmpDir, 0755, true);
        try {
            $contacts = load_contacts_from_files($tmpDir);
            $t->assertEmpty($contacts);
        } finally {
            rmdir($tmpDir);
        }
    });

    $t->run('loads valid fixture CSV correctly', function () use ($t, $fixturesDir) {
        $contacts = load_contacts_from_files($fixturesDir . '/valid');
        $t->assertCount(2, $contacts);
        $t->assertEqual('Alice Test', $contacts[0]['name']);
        $t->assertEqual('alice@test.com', $contacts[0]['email']);
        $t->assertEqual('Bob Demo', $contacts[1]['name']);
    });

    $t->run('skips CSV with wrong header', function () use ($t, $fixturesDir) {
        $contacts = load_contacts_from_files($fixturesDir . '/bad_header');
        $t->assertEmpty($contacts);
    });

    $t->run('skips rows with fewer than 5 columns', function () use ($t, $fixturesDir) {
        $contacts = load_contacts_from_files($fixturesDir . '/short_rows');
        $t->assertCount(1, $contacts, 'Only the valid 5-column row should be loaded');
        $t->assertEqual('Complete Row', $contacts[0]['name']);
    });

    $t->run('skips rows where both name and email are empty', function () use ($t, $fixturesDir) {
        $contacts = load_contacts_from_files($fixturesDir . '/empty_names');
        $t->assertCount(1, $contacts, 'Only the row with a name or email should be loaded');
        $t->assertEqual('Has Name', $contacts[0]['name']);
    });
}
