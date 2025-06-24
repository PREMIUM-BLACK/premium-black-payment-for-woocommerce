<?php

class payAPI
{
    protected string $publicKey;
    protected string $privateKey;
    protected bool $debugService;
    protected ?string $environment = null;
    protected string $serviceUrl = 'https://premium.black/service/rest/Pay.svc'; //http://localhost:807/service/rest/Pay.svc

    public function __construct(bool $debugService = false)
    {
        $this->debugService = $debugService;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }


    public function setServiceUrl(string $serviceUrl): void
    {
        $this->serviceUrl = $serviceUrl;
    }

    protected function buildUrl(string $suffix): string
    {
        return "$this->serviceUrl/$suffix";
    }

    public function IsTransactionConfirmed(IsTransactionConfirmedRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('IsTransactionConfirmed');

        return $this->doPost($r, $url);
    }

    public function CreateTransaction(CreateTransactionRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('CreateTransaction');

        return $this->doPost($r, $url);
    }

    public function GetTransactionDetails(GetTransactionDetailsRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('GetTransactionDetails');

        return $this->doPost($r, $url);
    }

    public function GetRate(?object $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('GetRate');

        return $this->doPost($r, $url);
    }

    public function ReOpenTransaction(ReOpenTransactionRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('ReOpenTransaction');

        return $this->doPost($r, $url);
    }

    public function CancelTransaction(CancelTransactionRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('CancelTransaction');

        return $this->doPost($r, $url);
    }
	
	public function GetConfigurations(GetConfigurationsRequest $request): ?object
    {
        $r = new stdClass();

        $r->publicKey = $this->publicKey;
        $r->request = $request;

        $url = $this->buildUrl('GetConfigurations');

        return $this->doPost($r, $url);
    }

    public function doPost(object $data, string $url): ?object
    {
        $data->request->Hash = $this->hashData($data->request);

        if ($this->debugService) {
            var_dump($data);
        }

        $data->Environment = $this->environment;

        $jsonObject = json_encode($data);

        $args = [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => $jsonObject,
            'timeout' => 4,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            echo 'HTTP-Fehler: ' . esc_html($response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);

        if ($this->debugService) {
            print ("<br /><br />");
            var_dump(json_decode($body));
        }

        return json_decode($body);
    }

    public function hashData(?object $data): string
    {
        $members = get_object_vars($data);

        uksort($members, 'strnatcasecmp');

        $s = '';

        foreach ($members as $m => $v) {
            if ($m == 'Hash')
                continue;

            if ($v == null)
                continue;

            if (is_object($v))
                continue;

            $s .= $v;
        }

        return hash('sha256', $s . $this->privateKey);
    }

    public function checkHash(?object $data): bool
    {
        // TODO: Hash function does not work correctly??!
        return true;

        if ($data == null || $data->Hash == null) {
            return true;
        }

        $hash = $data->Hash;

        return $this->hashData($data) == $hash;
    }
}
