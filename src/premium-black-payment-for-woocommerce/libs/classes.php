<?php

class Premblpa_IsTransactionConfirmedRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public 	$TransactionKey = '';
}

class Premblpa_CreateTransactionRequest
{
	public $Hash = '';

	public $Currency = '';

	public $Blockchain = '';

	public $Amount = '';
	public $PriceCurrency = '';
	public $IPN = '';
	public $BlockAddress = '';

	public $CustomData;
	public $CustomUserId;
	public $CustomerMail;
	public $CustomOrderId;
}

class Premblpa_GetTransactionDetailsRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public $TransactionKey = '';

	public $ReturnQRCode = 'false';
}

class Premblpa_ReOpenTransactionRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public 	$TransactionKey = '';
}

class Premblpa_CancelTransactionRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public $TransactionKey = '';
	public $ByCustomer = 'false';
}

class Premblpa_GetConfigurationsRequest
{
	public $Hash = '';

}