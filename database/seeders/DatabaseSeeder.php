<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Dossier;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────────
        $users = [
            ['nom' => 'Bertin',    'prenom' => 'Marie-Sophie', 'initiales' => 'MSB', 'role' => 'admin'],
            ['nom' => 'Alain',     'prenom' => 'Christophe',   'initiales' => 'CA',  'role' => 'gestionnaire'],
            ['nom' => 'Bousquet',  'prenom' => 'Eric',          'initiales' => 'EB',  'role' => 'gestionnaire'],
            ['nom' => 'Martin',    'prenom' => 'Nicolas',        'initiales' => 'NM',  'role' => 'gestionnaire'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(['email' => strtolower($u['initiales']).'@kehitaa.com'], [
                ...$u,
                'password' => Hash::make('password'),
                'actif'    => true,
            ]);
        }

        // ── Clients ────────────────────────────────────────────────
        $clients = [
            ['nom' => 'PERENCO',          'pays' => 'Cameroun'],
            ['nom' => 'TOTAL',            'pays' => 'République Du Congo'],
            ['nom' => 'CALLIDUS',         'pays' => 'Nouvelle Calédonie'],
            ['nom' => 'SPIE',             'pays' => 'République Du Congo'],
            ['nom' => 'TRIDENT',          'pays' => 'Guinée Équatoriale'],
            ['nom' => 'SOGARA',           'pays' => 'Gabon'],
            ['nom' => 'ASSALA',           'pays' => 'Gabon'],
            ['nom' => 'CORAF',            'pays' => 'République Du Congo'],
            ['nom' => 'SOMAIR',           'pays' => 'Niger'],
            ['nom' => 'WESCO',            'pays' => 'USA'],
            ['nom' => 'ATLANTIC METHANOL','pays' => 'Guinée Équatoriale'],
        ];

        foreach ($clients as $c) {
            Client::firstOrCreate(['nom' => $c['nom']], $c);
        }

        // ── Fournisseurs ───────────────────────────────────────────
        $fournisseurs = [
            ['nom' => 'Masoneilan AF-Sud',                            'pays' => 'France',  'ville' => 'Condé-sur-Noireau'],
            ['nom' => 'Dresser Produits Industriels SAS - France',    'pays' => 'France',  'ville' => 'Condé-sur-Noireau'],
            ['nom' => 'Dresser Italia S.r.l. Naples - Italie',        'pays' => 'Italie',  'ville' => 'Naples'],
            ['nom' => 'Bently Nevada - USA',                          'pays' => 'USA',     'ville' => 'Minden'],
            ['nom' => 'Broady - UK',                                  'pays' => 'UK',      'ville' => 'Hull'],
            ['nom' => 'Score - UK',                                   'pays' => 'UK',      'ville' => 'Peterhead'],
            ['nom' => 'GROTH - USA',                                  'pays' => 'USA',     'ville' => 'Stafford'],
            ['nom' => 'Sullivan',                                     'pays' => 'USA',     'ville' => 'Haughton'],
            ['nom' => 'PEKOS',                                        'pays' => 'Espagne', 'ville' => 'Montmeló'],
            ['nom' => 'CVS',                                          'pays' => 'Canada',  'ville' => 'Edmonton'],
        ];

        foreach ($fournisseurs as $f) {
            Fournisseur::firstOrCreate(['nom' => $f['nom']], $f);
        }

        $this->command->info('✓ Seed terminé — Utilisateurs, clients, fournisseurs créés.');
        $this->command->info('  Login admin : msb@kehitaa.com / password');
    }
}
