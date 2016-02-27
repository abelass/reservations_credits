<?php
/*
 * Paiement Bancaire
 * module de paiement bancaire multi prestataires
 * stockage des transactions
 *
 * Auteurs :
 * Cedric Morin, Nursit.com
 * (c) 2012-2015 - Distribue sous licence GNU/GPL
 *
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
	
	$action = charger_fonction('editer_objet', 'action');
	
	$action('new', 'reservation_credit_mouvement',$set);
	
	$set = array(
		'type' => 'debit',
		'email' => _request('email_client'),
	)
	
	return bank_simple_call_response($config, $response);
}
