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

  if ($table == 'spip_evenements'){
    
    // Si un événement publié est annulé
    if($flux['args']['statut_ancien'] == 'publie' AND $flux['data']['statut'] == 'annule') 
      set_request('instituer_credit_mouvement','credit');
    // Ou si un événment annulé est republié
    elseif($flux['args']['statut_ancien'] == 'annule' AND $flux['data']['statut'] == 'publie')
      set_request('instituer_credit_mouvement','debit');
    
    // On crée les crédits pour chaque détail de réservation payé
    if ($type = _request('instituer_credit_mouvement')) {
      set_request('type',$type);
      $action = charger_fonction('editer_objet', 'action');
      $sql = sql_select('id_reservations_detail, id_auteur, email, prix_ht, prix, taxe,descriptif',
        'spip_reservations_details LEFT JOIN spip_reservations USING (id_reservation)',
        'id_evenement=' . $flux['args']['id_objet'] . ' AND spip_reservations_details.statut="accepte"');
      $date = date('Y-m-d H:i:s');
      while($data = sql_fetch($sql)) {
        if (isset($data['id_auteur']) AND $email = sql_getfetsel('email','spip_auteurs','id_auteur =' . $data['id_auteur'])){
        }
        else
         $email = $data['email'];
         
        set_request('email',$email ) ;
        set_request('id_reservations_detail',$data['id_reservations_detail']) ;
        set_request('descriptif',_T('reservation_credit_mouvement:_mouvement_descriptif',array('titre'=>$data['descriptif'])));
        
        // On établit le montant
        if ($data['prix'] > 0){
          set_request('montant',$data['prix']);
        }
        else {
          $montant = $data['prix_ht'] + $data['taxe'];
          
          set_request('montant',$montant);
        }
        set_request('date_creation',$date);
        
        // Création du crédit
        $action('new', 'reservation_credit_mouvement');
      }
    }
  }
  return $flux;
}
