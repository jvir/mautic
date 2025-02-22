<?php

namespace MauticPlugin\MauticCrmBundle\Controller;

use function assert;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\CompanyImport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\LeadImport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\OwnerImport;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class PipedriveController.
 */
class PipedriveController extends CommonController
{
    public const INTEGRATION_NAME = 'Pipedrive';

    public const LEAD_ADDED_EVENT  = 'added.person';
    public const LEAD_UPDATE_EVENT = 'updated.person';
    public const LEAD_DELETE_EVENT = 'deleted.person';

    public const COMPANY_ADD_EVENT    = 'added.organization';
    public const COMPANY_UPDATE_EVENT = 'updated.organization';
    public const COMPANY_DELETE_EVENT = 'deleted.organization';

    public const USER_ADD_EVENT    = 'added.user';
    public const USER_UPDATE_EVENT = 'updated.user';

    /**
     * @return JsonResponse
     */
    public function webhookAction(Request $request, IntegrationHelper $integrationHelper, LeadImport $leadImport, CompanyImport $companyImport, OwnerImport $ownerImport)
    {
        $pipedriveIntegration = $integrationHelper->getIntegrationObject(self::INTEGRATION_NAME);

        if (!$pipedriveIntegration || !$pipedriveIntegration->getIntegrationSettings()->getIsPublished()) {
            return new JsonResponse([
                'status' => 'Integration turned off',
            ], Response::HTTP_OK);
        }

        assert($pipedriveIntegration instanceof PipedriveIntegration);
        if (!$this->validCredential($request, $pipedriveIntegration)) {
            throw new UnauthorizedHttpException('Basic');
        }

        $params   = json_decode($request->getContent(), true);
        $data     = $params['current'];
        $response = [
            'status' => 'ok',
        ];

        try {
            switch ($params['event']) {
                case self::LEAD_UPDATE_EVENT:
                    $leadImport = $this->getLeadImport($pipedriveIntegration, $leadImport);
                    $leadImport->update($data);
                    break;
                case self::LEAD_DELETE_EVENT:
                    $leadImport = $this->getLeadImport($pipedriveIntegration, $leadImport);
                    $leadImport->delete($params['previous']);
                    break;
                case self::COMPANY_UPDATE_EVENT:
                    $companyImport = $this->getCompanyImport($pipedriveIntegration, $companyImport);
                    $companyImport->update($data);
                    break;
                case self::COMPANY_DELETE_EVENT:
                    $companyImport = $this->getCompanyImport($pipedriveIntegration, $companyImport);
                    $companyImport->delete($params['previous']);
                    break;
                case self::USER_UPDATE_EVENT:
                    $ownerImport = $this->getOwnerImport($pipedriveIntegration, $ownerImport);
                    $ownerImport->create($data[0]);
                    break;
                default:
                    $response = [
                        'status' => 'unsupported event',
                    ];
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $this->getErrorCodeFromException($e));
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Transform unknown Exception codes into 500 code.
     *
     * @return int
     */
    private function getErrorCodeFromException(\Exception $e)
    {
        $code = $e->getCode();

        return (is_int($code) && $code >= 400 && $code < 600) ? $code : 500;
    }

    /**
     * @param $integration
     *
     * @return LeadImport
     */
    private function getLeadImport($integration, LeadImport $leadImport)
    {
        $leadImport->setIntegration($integration);

        return $leadImport;
    }

    /**
     * @param $integration
     *
     * @return CompanyImport
     */
    private function getCompanyImport($integration, CompanyImport $companyImport)
    {
        $companyImport->setIntegration($integration);

        return $companyImport;
    }

    /**
     * @param $integration
     *
     * @return OwnerImport
     */
    private function getOwnerImport($integration, OwnerImport $ownerImport)
    {
        $ownerImport->setIntegration($integration);

        return $ownerImport;
    }

    /**
     * @return bool
     */
    private function validCredential(Request $request, PipedriveIntegration $pipedriveIntegration)
    {
        $headers = $request->headers->all();
        $keys    = $pipedriveIntegration->getKeys();

        if (!isset($headers['authorization']) || !isset($keys['user']) || !isset($keys['password'])) {
            return false;
        }

        $basicAuthBase64       = explode(' ', $headers['authorization'][0]);
        $decodedBasicAuth      = base64_decode($basicAuthBase64[1]);
        list($user, $password) = explode(':', $decodedBasicAuth);

        if ($keys['user'] == $user && $keys['password'] == $password) {
            return true;
        }

        return false;
    }
}
