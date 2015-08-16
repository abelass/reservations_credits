<?php
/**
 * Utilisations de pipelines par Réseŕvations Crédits
 *
 * @plugin     Réseŕvations Crédits
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservations_credits\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION'))
  return;
/*
 * Un fichier de pipelines permet de regrouper
 * les fonctions de branchement de votre plugin
 * sur des pipelines existants.
 */

/**
 * Intervient après le changement de statut d'un objet
 *
 * @pipeline post_edition
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservations_credits_post_edition($flux) {
  $table = $flux['args']['table'];

  if ($table == 'spip_evenements' AND $flux['data']['statut'] == 'annule') {
    $action = charger_fonction('editer_objet', 'action');
    $sql = sql_select('id_reservations_detail, id_auteur, email, prix_ht, prix, taxe',
      'spip_reservations_details LEFT JOIN spip_reservations USING (id_reservation)',
      'id_evenement=' . $flux['args']['id_objet'] . ' AND spip_reservations_details.statut="accepte"');
    $date = date('Y-m-d H:i:s');
    while($data = sql_fetch($sql)) {
      set_request('id_reservations_detail',$data['id_reservations_detail']) ;
      set_request('id_auteur',$data['id_auteur']);
      set_request('email',$data['email']);
      if ($data['prix'] > 0){
        set_request('montant',$data['prix']);
      }
      else {
        $montant = $data['prix_ht'] + $data['taxe'];
        set_request('montant',$montant);
      }
      set_request('date_creation',$date);
      $action('new', 'reservation_credit');
    }
    
  }
  return $flux;
}

/**
 * Ajout de liste sur la vue d'un auteur
 *
 * @pipeline affiche_auteurs_interventions
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservations_credits_affiche_auteurs_interventions($flux) {
  if ($id_auteur = intval($flux['args']['id_auteur'])) {

    $flux['data'] .= recuperer_fond('prive/objets/liste/reservation_credits', array(
      'id_auteur' => $id_auteur,
      'titre' => _T('reservation_credit:info_reservation_credits_auteur')
    ), array('ajax' => true));

  }
  return $flux;
}
