<?php

use CQ\DB\Seeder;

class SitesSeeder extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $faker = Seeder::faker();
        $data = [];

        for ($i = 0; $i < 5; ++$i) {
            $data[] = [
                'id' => $faker->uuid,
                'user_id' => $faker->uuid,
                'user_email' => $faker->email,
                'name' => $faker->domainName,
                'domains' => $faker->domainName,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->table('sites')->insert($data)->saveData();
    }
}
