<?php
/**
 * Déclarations relatives à la base de données
 *
 * @plugin     Réseŕvations Crédits
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservations_credits\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION'))
  return;

/**
 * Déclaration des alias de tables et filtres automatiques de champs
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function reservations_credits_declarer_tables_interfaces($interfaces) {

  $interfaces['table_des_tables']['reservation_credits'] = 'reservation_credits';

  return $interfaces;
}

/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function reservations_credits_declarer_tables_objets_sql($tables) {

  $tables['spip_reservation_credits'] = array(
    'type' => 'reservation_credit',
    'principale' => "oui",
    'table_objet_surnoms' => array('reservationcredit'), // table_objet('reservation_credit') => 'reservation_credits'
    'field' => array(
      "id_reservation_credit" => "bigint(21) NOT NULL",
      "id_reservations_detail" => "int(11) NOT NULL DEFAULT 0",
      "id_auteur" => "int(11) NOT NULL DEFAULT 0",
      "email" => "varchar(100) NOT NULL DEFAULT ''",
      "descriptif" => "text NOT NULL DEFAULT ''",
      "montant" => "float NOT NULL DEFAULT '0'",
      "date_creation" => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
      "maj" => "TIMESTAMP"
    ),
    'key' => array(
      "PRIMARY KEY" => "id_reservation_credit",
      "KEY id_reservations_detail" => "id_reservations_detail",
      "KEY id_auteur" => "id_auteur",
      "KEY email" => "email",
      ),

    'titre' => "'' AS titre, '' AS lang",
    'champs_editables' => array(
      'id_detail_reservation',
      'id_auteur',
      'email',
      'descriptif',
      'montant',
      'date_creation'
    ),
    'champs_versionnes' => array(
      'id_detail_reservation',
      'id_auteur',
      'email',
      'descriptif',
      'montant',
      'date_creation'
    ),
    'rechercher_champs' => array("montant" => 8),
    'tables_jointures' => array(),
  );
  
  // Ajouter le statut annulé aux événements
  $tables['spip_evenements']['statut_textes_instituer']['annule']='reservation_credit:texte_statut_annule';
  $tables['spip_evenements']['statut_images']=array(
            'prop'=>'puce-proposer-8.png',
            'publie'=>'puce-publier-8.png',
            'annule'=>'puce-refuser-8.png',
            'poubelle'=>'puce-supprimer-8.png',
        );  

  return $tables;
}
