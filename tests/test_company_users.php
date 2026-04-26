<?php
declare(strict_types=1);

function run_company_user_tests(TestRunner $t): void
{
    $t->section('Company Users');

    require_once PROJECT_ROOT . '/includes/company_users.php';

    $t->run('sample seed contains at least 20 complete users', function () use ($t) {
        $users = get_sample_company_users();
        $t->assertGreaterThan(19, count($users), 'Sample seed should include at least 20 users');

        foreach ($users as $user) {
            foreach (['first_name', 'last_name', 'email', 'home_address', 'home_phone', 'cell_phone'] as $field) {
                $t->assertNotEmpty($user[$field] ?? '', "Sample user should include {$field}");
            }
        }
    });

    $t->run('valid user input passes validation', function () use ($t) {
        $result = validate_company_user_input([
            'first_name' => 'Taylor',
            'last_name' => 'Morgan',
            'email' => 'taylor.morgan@passandplay.com',
            'home_address' => '100 First St, San Jose, CA 95113',
            'home_phone' => '408-555-1200',
            'cell_phone' => '408-555-2200',
        ]);

        $t->assertEmpty($result['errors'], 'Valid input should not produce validation errors');
        $t->assertEqual('taylor.morgan@passandplay.com', $result['values']['email']);
    });

    $t->run('invalid user input reports required contact fields', function () use ($t) {
        $result = validate_company_user_input([
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'home_address' => '',
            'home_phone' => '123',
            'cell_phone' => 'abc',
        ]);

        foreach (['first_name', 'last_name', 'email', 'home_address', 'home_phone', 'cell_phone'] as $field) {
            $t->assertTrue(isset($result['errors'][$field]), "Validation should flag {$field}");
        }
    });
}
