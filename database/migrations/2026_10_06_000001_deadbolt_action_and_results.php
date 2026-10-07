<?php

use ExpressionEngine\Service\Migration\Migration;

class DeadboltActionAndResults extends Migration
{
    public function up()
    {
        ee()->load->dbforge();
        ee()->dbforge->add_field([
            'nonce_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
            ],
            'session_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
            ],
            'lock_name' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'key_valid' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'captcha_error' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'expires_at' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
        ]);
        ee()->dbforge->add_key('nonce_hash', true);
        ee()->dbforge->add_key('expires_at');
        ee()->dbforge->create_table('deadbolt_results', true, ['ENGINE' => 'InnoDB']);

        ee('Model')->make('Action', [
            'class' => 'Deadbolt',
            'method' => 'SubmitKey',
            'csrf_exempt' => false,
        ])->save();
    }

    public function down()
    {
        ee('Model')->get('Action')
            ->filter('class', 'Deadbolt')
            ->filter('method', 'SubmitKey')
            ->delete();

        ee()->load->dbforge();
        ee()->dbforge->drop_table('deadbolt_results', true);
    }
}
