<?php

namespace App\Support;

use Symfony\Component\Intl\Countries;

class Pays
{
    /**
     * Liste standard des pays (noms français, ISO 3166 via symfony/intl — données
     * CLDR officielles, à jour), utilisée pour les <select> de pays dans les
     * formulaires Client / Fournisseur / Transporteur / Dossier.
     */
    public static function liste(): array
    {
        return array_values(Countries::getNames('fr'));
    }

    /**
     * Options pour un <select> : la liste standard + la valeur actuellement stockée
     * si elle n'y figure pas (ex. anciennes saisies libres), pour ne jamais l'écraser
     * silencieusement à l'ouverture d'un formulaire d'édition.
     */
    public static function optionsAvec(?string $valeurActuelle): array
    {
        $options = self::liste();

        if ($valeurActuelle && ! in_array($valeurActuelle, $options, true)) {
            array_unshift($options, $valeurActuelle);
        }

        return $options;
    }
}
