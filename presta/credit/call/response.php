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
if (! defined ( '_ECRIRE_INC_VERSION' ))
	return;

/**
 * il faut avoir un id_transaction et un transaction_hash coherents
 * pour se premunir d'une tentative d'appel exterieur
 *
 * @param array $config        	
 * @param null|array $response        	
 * @return array
 */
function presta_gratuit_call_response_dist($config, $response = null) {
	$mode = $config ['presta'];
	// recuperer la reponse en post et la decoder, en verifiant la signature
	if (! $response)
		$response = bank_response_simple ( $mode );
	
	if (! isset ( $response ['id_transaction'] ) or ! isset ( $response ['transaction_hash'] )) {
		return bank_transaction_invalide ( 0, array(
			'mode' => $mode,
			'erreur' => "id_transaction ou transaction_hash absent",
			'log' => bank_shell_args ( $response ) 
		) );
	}
	
	$id_transaction = $response ['id_transaction'];
	$transaction_hash = $response ['transaction_hash'];
	
	if (! $row = sql_fetsel ( '*', 'spip_transactions', 'id_transaction=' . intval ( $id_transaction ) )) {
		return bank_transaction_invalide ( $id_transaction, array(
			'mode' => $mode,
			'erreur' => "transaction inconnue",
			'log' => bank_shell_args ( $response ) 
		) );
	}
	if ($transaction_hash != $row ['transaction_hash']) {
		return bank_transaction_invalide ( $id_transaction, array(
			'mode' => $mode,
			'erreur' => "id_transaction $id_transaction, hash $transaction_hash non conforme",
			'log' => bank_shell_args ( $response ) 
		) );
	}
	
	// verifier si le montant payé est égale ou inférieure au monant requis
	
	// Le cas normal d'une reservation
	if ($id_reservation = $row ['id_reservation']) {
		$sql = sql_select ( 'prix,prix_ht,taxe,quantite', 'spip_reservations_details', 'status!=' . sql_quote ( 'poubelle' ) . ' AND id_reservation=' . $id_reservation );
		$prix = array();
		while ( $row = sql_fetch ( $sql ) ) {
			if (intval ( $row ['prix'] ) > 0 or floatval ( $row ['prix'] ) > 0.00) {
				$prix [] = $row ['prix'] * $row ['quantite'];
			}
			else {
				$prix [] = $row ['prix_ht'] * $row ['quantite'];
			}
			$prix_du = array_sum ( $prix );
		}
	}
	elseif ($id_commande = $row ['id_commande']) {
		$sql = sql_select ( 'prix,prix_ht,taxe,quantite', 'spip_reservations_details', 'status!=' . sql_quote ( 'poubelle' ) . ' AND id_reservation=' . $id_reservation );
		$prix = array();
		while ( $row = sql_fetch ( $sql ) ) {
			if (intval ( $row ['prix'] ) > 0 or floatval ( $row ['prix'] ) > 0.00) {
				$prix [] = $row ['prix'] * $row ['quantite'];
			}
			else {
				$prix [] = $row ['prix_ht'] * $row ['quantite'];
			}
			$prix_du = array_sum ( $prix );
		}
	}
}

if (intval ( $row ['montant'] ) > 0 or floatval ( $row ['montant'] ) > 0.00) {
	return bank_transaction_invalide ( $id_transaction, array(
		'mode' => $mode,
		'erreur' => "id_transaction $id_transaction, montant " . $row ['montant'] . ">0 interdit",
		'log' => bank_shell_args ( $response ) 
	) );
}

// OK, on peut accepter le reglement
$set = array(
	"mode" => $mode,
	"montant_regle" => $row ['montant'],
	"date_paiement" => date ( 'Y-m-d H:i:s' ),
	"statut" => 'ok',
	"reglee" => 'oui' 
);
sql_updateq ( "spip_transactions", $set, "id_transaction=" . intval ( $id_transaction ) );
spip_log ( "call_resonse : id_transaction $id_transaction, reglee", $mode );

$regler_transaction = charger_fonction ( 'regler_transaction', 'bank' );
$regler_transaction ( $id_transaction, array(
	'row_prec' => $row 
) );

return array(
	$id_transaction,
	true 
);
}

