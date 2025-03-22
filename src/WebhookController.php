<?php
require_once __DIR__ . "/../crest/crest.php";
require_once __DIR__ . "/../utils.php";

define('CONFIG', require_once __DIR__ . '/../config.php');

class WebhookController
{
    private const ALLOWED_ROUTES = [
        'dealUpdated' => 'handleDealUpdated',
    ];

    private LoggerController $logger;
    private BitrixController $bitrix;
    private UtilsController $utils;

    public function __construct()
    {
        $this->logger = new LoggerController();
        $this->bitrix = new BitrixController();
        $this->utils = new UtilsController();
    }

    // Handles incoming webhooks
    public function handleRequest(string $route): void
    {
        try {
            $this->logger->logRequest($route);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->utils->sendResponse(405, [
                    'error' => 'Method Not Allowed. Only POST is accepted.'
                ]);
            }

            if (!array_key_exists($route, self::ALLOWED_ROUTES)) {
                $this->utils->sendResponse(404, [
                    'error' => 'Resource not found'
                ]);
            }

            $handlerMethod = self::ALLOWED_ROUTES[$route];

            $data = $this->utils->parseRequestData();
            if ($data === null) {
                $this->utils->sendResponse(400, [
                    'error' => 'Invalid JSON data'
                ]);
            }

            $this->$handlerMethod($data);
        } catch (Throwable $e) {
            $this->logger->logError('Error processing request', $e);
            $this->utils->sendResponse(500, [
                'error' => 'Internal server error'
            ]);
        }
    }

    // Handles callStarted webhook event
    public function handleDealUpdated(array $data): void
    {
        $this->logger->logWebhook('deal_updated', $data);

        // Process incoming data
        $event = $data['event'];

        if ($event !== 'ONCRMDEALUPDATE') {
            $this->utils->sendResponse(400, [
                'error' => 'Invalid event type'
            ]);
        }

        $dealId = $data['data']['FIELDS']['ID'];
        $deal = $this->bitrix->getDeal($dealId);

        if (!$this->utils->isValidDeal($deal)) {
            $this->utils->sendResponse(400, [
                'error' => 'Invalid deal stage'
            ]);
        }

        $contact = $this->bitrix->getContact($deal['CONTACT_ID']);
        $this->logger->logInfo('contact', $contact);

        if ($contact === null) {
            $this->utils->sendResponse(400, [
                'error' => 'Contact not found'
            ]);
        }

        $name = trim($contact['NAME'] . ' ' . $contact['LAST_NAME']);
        $phone = $contact['PHONE'][0]['VALUE'];
        $email = $contact['EMAIL'][0]['VALUE'];

        $lead = $this->bitrix->addLead(CONFIG['LEAD_ENTITY_TYPE_ID'], [
            'ufCrm185Name' => $name,
            'ufCrm185Phone' => $phone,
            'ufCrm185Email' => $email,
            'ufCrm185DealId' => $dealId,
            'categoryId' => CONFIG['AVAILABILITY_PIPELINE_ID'],
        ]);

        if ($lead === null) {
            $this->utils->sendResponse(500, [
                'error' => 'Failed to create secondary lead'
            ]);
        }

        $this->logger->logInfo('lead', $lead);
        $leadId = $lead['id'];

        $this->utils->sendResponse(200, [
            'message' => 'Deal updated data processed successfully and secondary lead created with ID: ' . $leadId,
        ]);
    }
}
