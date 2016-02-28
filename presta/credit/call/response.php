<?php
/**
 * Stockage des transactionsde pipelines par Réservations Crédits
 *
 * @plugin     Réseŕvations Crédits
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservations_credits\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Call response simple (cheque, virement)
 * il faut avoir un id_transaction et un transaction_hash coherents
 * pour se premunir d'une tentative d'appel exterieur
 *
 * @param array $config
 * @param null|array $response
 * @return array
 */
function presta_credit_call_response_dist($config, $response=null){
	include_spip('inc/bank');

	// Enregistrer un mouvement crédit
	$id_reservation = session_get('encaisser_id_reservation');
	$id_transaction = session_get('encaisser_id_transaction');

	if(!$montant = session_get('encaisser_montant_regle')) {
		$montant = sql_getfetsel('montant','spip_transactions','id_transaction=' . $id_transaction);
	} 
	$action = charger_fonction('editer_objet', 'action');
	
	$donnees = sql_fetsel('spip_reservations_details.devise,reference,email,id_auteur', 'spip_reservations LEFT JOIN spip_reservations_details USING (id_reservation)', 'spip_reservations.id_reservation=' . $id_reservation);
	if (!$email = $donnees['email']) {
		$email = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . $donnees['id_auteur']);
	}

	$reference= $donnes['reference'];

	$set = array(
		'type' => 'debit',
		'email' => $email,
		'descriptif' => _T('reservation_bank:paiement_reservation', 
			array(
				'id_reservation' => $id_reservation)
			),
		'id_reservation' => $id_reservation,
		'montant' => $montant,
		'devise' => $donnees['devise'],
	);

	$action('new', 'reservation_credit_mouvement',$set);
	
	session_set('encaisser_id_reservation','');
	session_set('encaisser_id_transaction','');
	session_set('encaisser_montant_regle','');
	
	return bank_simple_call_response($config, $response);
}
