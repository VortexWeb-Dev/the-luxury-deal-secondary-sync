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

        $unitNumber = $deal['UF_CRM_1730722137745'];
        $remarks = $deal['UF_CRM_1730870220689'];
        $title = $deal['TITLE'];

        $unitPurpose = $this->utils->getUnitPurposeId($deal['UF_CRM_1730953177974']); // Not complete
        $propertyType = $this->utils->getPropertyTypeId($deal['UF_CRM_672DD64B4AEA1']);
        $unitStatus = $this->utils->getUnitStatusId($deal['UF_CRM_1730954607222']);
        $bedrooms = $this->utils->getBedroomsId($deal['UF_CRM_67178A4525DE2']);
        $managerApproval = $this->utils->geManagerApprovalId($deal['UF_CRM_1730805220080']);

        $leadData = [
            'title' => $title,
            'ufCrm9_1735639216243' => $title,
            'ufCrm9_1743737461' => $name,
            'ufCrm9_1741866044408' => $phone,
            'ufCrm9_1741866064161' => $email,
            'ufCrm9_1743737418' => $dealId,
            'ufCrm9_1733832564093' => $unitNumber,
            'ufCrm9_1733833886' => $remarks,
            'ufCrm9_1733832278562' => $propertyType,
            'ufCrm9_1733833012975' => $unitStatus,
            'ufCrm9_1733833194417' => $bedrooms,
            'ufCrm9_1734700123397' => $managerApproval,
            'ufCrm9_1733832784377' => $unitPurpose,
            'categoryId' => CONFIG['AVAILABILITY_PIPELINE_ID'],
        ];

        $existingLead = $this->bitrix->getLeadByDealId(CONFIG['SECONDARY_ENTITY_TYPE_ID'], $dealId);

        if ($existingLead !== null) {
            $leadId = $existingLead['id'];
            $this->bitrix->updateLead(CONFIG['SECONDARY_ENTITY_TYPE_ID'], $leadData, $leadId);
            $this->logger->logInfo('lead_updated', [
                'leadId' => $leadId,
                'data' => $leadData
            ]);
            $this->utils->sendResponse(200, [
                'message' => 'Deal updated data processed successfully and lead updated with ID: ' . $leadId,
            ]);
        }

        $lead = $this->bitrix->addLead(CONFIG['SECONDARY_ENTITY_TYPE_ID'], $leadData);

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
