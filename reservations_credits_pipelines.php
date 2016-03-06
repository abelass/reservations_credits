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
    $statut_ancien = $flux['args']['statut_ancien'];
    $statut = $flux['data']['statut'];
    // Si un événement publié est annulé
    if($statut_ancien == 'publie' AND $statut == 'annule')
      set_request('instituer_credit_mouvement','credit');

    // Ou si un événment annulé est republié
    elseif($statut_ancien == 'annule' AND $statut == 'publie')
      set_request('instituer_credit_mouvement','debit');

    // On crée les crédits pour chaque détail de réservation payé
    if ($type = _request('instituer_credit_mouvement')) {
      set_request('type',$type);
      $action = charger_fonction('editer_objet', 'action');
      if (test_plugin_actif('prix_objets'))
         $sql = sql_select('id_reservations_detail, id_auteur, email, spip_reservations_details.prix_ht, spip_reservations_details.prix, spip_reservations_details.taxe,descriptif,code_devise',
        'spip_reservations_details LEFT JOIN spip_reservations USING (id_reservation) LEFT JOIN spip_prix_objets USING (id_prix_objet)',
        'id_evenement=' . $flux['args']['id_objet'] . ' AND spip_reservations_details.statut="accepte"');
      else
        $sql = sql_select('id_reservations_detail, id_auteur, email, prix_ht, prix, taxe,descriptif',
        'spip_reservations_details LEFT JOIN spip_reservations USING (id_reservation)',
        'id_evenement=' . $flux['args']['id_objet'] . ' AND spip_reservations_details.statut="accepte"');

      $date = date('Y-m-d H:i:s');

      while($data = sql_fetch($sql)) {
        if (!isset($data['id_auteur']) OR !$email = sql_getfetsel('email','spip_auteurs','id_auteur =' . $data['id_auteur'])) $email = $data['email'];

        if (isset($data['code_devise'])) set_request('devise',$data['code_devise']);
        set_request('email',$email );
        set_request('id_reservations_detail',$data['id_reservations_detail']) ;
        set_request('descriptif',_T('reservation_credit_mouvement:mouvement_evenement_' . $statut,array('titre'=>$data['descriptif'])));

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

/**
 * Permet d’ajouter du contenu dans la colonne « gauche » des pages de l’espace privé.
 *
 * @pipeline affiche_gauche
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservations_credits_affiche_gauche($flux) {
    $exec = $flux["args"]["exec"];
  // reservations sur les evenements
  if ($exec == 'client') {
    $contexte = calculer_contexte();
    $data .= recuperer_fond('prive/gauche/credit', $contexte);
    $flux['data'] .= $data;
  }
  return $flux;
}

/**
 * Permet d’ajouter du contenu dans le menu admin du plugin réservation.
 *
 * @pipeline affiche_gauche
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservations_credits_reservation_evenement_menu_admin($flux) {
  // reservations sur les evenements
    $contexte = calculer_contexte();
    $data .= recuperer_fond('prive/gauche/menu_admin_credit', $contexte);
    $flux['data'] .= $data;

  return $flux;
}

/**
 * Déduire le montant payé des crédits
 * 
 * @pipeline bank_traiter_reglement
 * @param array $flux
 * @return array mixed
 */
function reservations_credits_bank_traiter_reglement($flux){
	// Si on est dans le bon cas d'un paiement de reservation et qu'il y a un id_reservation et que la reservation existe toujours
	if (
		$id_transaction = $flux['args']['id_transaction']
		and $transaction = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))
		and $id_reservation = $transaction['id_reservation']
	){
		$montant_reservations_detail_total = _request('montant_reservations_detail_total') ? _request('montant_reservations_detail_total') : array();
		
		$paiement_detail = array();
		foreach(array_keys($montant_reservations_detail_total) AS $id_reservation_detail ) {
			$paiement_detail[$id_reservation_detail] = _request('montant_reservations_detail_' . $id_reservation_detail);
		}
		
		if (!$montant = array_sum($paiement_detail)){
			$montant = $transaction['montant_regle'];
		}
		elseif (is_array($montant)) {
			$montant= array_sum($montant);
		}
		
	// Enregistrer un mouvement crédit
	$id_reservation = _request('id_reservation');

	$action = charger_fonction('editer_objet', 'action');
	
	$donnees = sql_fetsel('spip_reservations_details.devise,reference,email,id_auteur', 'spip_reservations LEFT JOIN spip_reservations_details USING (id_reservation)', 'spip_reservations.id_reservation=' . $id_reservation);
	if (!$email = $donnees['email']) {
		$email = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . $donnees['id_auteur']);
	}

	$reference= $donnes['reference'];

	$set = array(
		'type' => 'debit',
		'email' => $email,
		'descriptif' => _T('reservation_bank:paiement_reservation', array(
				'id_reservation' => $id_reservation
				)
			),
		'id_reservation' => $id_reservation,
		'montant' => $montant,
		'devise' => $donnees['devise'],
	);

	$action('new', 'reservation_credit_mouvement',$set);
	
	}

	return $flux;
}
