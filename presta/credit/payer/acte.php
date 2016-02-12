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
 * @param array $config
 * @param int $id_transaction
 * @param string $transaction_hash
 * @param array $options
 * @return array|string
 */
function presta_credit_payer_acte_dist($config,$id_transaction,$transaction_hash,$options=array()){

	include_spip("inc/bank");

	$contexte = array(
		'id_transaction' => $id_transaction,
		'transaction_hash' => $transaction_hash,
		'autorisation_id' => 'wait',
	);
	$contexte['sign'] = bank_sign_response_simple($config['presta'], $contexte);

	// url action
	$action = bank_url_api_retour($config,'response');
	foreach($contexte as $k=>$v){
		$action = parametre_url($action,$k,$v);
	}
	$contexte['action'] = $action;
	$contexte['config'] = $config;

	$contexte = array_merge($options, $contexte);

	return recuperer_fond('presta/credit/payer/acte', $contexte);
}

