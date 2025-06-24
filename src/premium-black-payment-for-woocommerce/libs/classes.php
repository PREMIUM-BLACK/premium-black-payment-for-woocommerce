<?php

class IsTransactionConfirmedRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public 	$TransactionKey = '';
}

class CreateTransactionRequest
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

class GetTransactionDetailsRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public $TransactionKey = '';

	public $ReturnQRCode = 'false';
}

class ReOpenTransactionRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public 	$TransactionKey = '';
}

class CancelTransactionRequest
{
	public $Hash = '';

	public $TransactionId = '';
	public $TransactionKey = '';
	public $ByCustomer = 'false';
}

class GetConfigurationsRequest
{
	public $Hash = '';

}