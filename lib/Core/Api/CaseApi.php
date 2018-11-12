<?php
/**
 * CaseApi for the Signifyd SDK
 *
 * PHP version 5.6
 *
 * @category  Signifyd_Fraud_Protection
 * @package   Signifyd\Core
 * @author    Signifyd <info@signifyd.com>
 * @copyright 2018 SIGNIFYD Inc. All rights reserved.
 * @license   See LICENSE.txt for license details.
 * @link      https://www.signifyd.com/
 */
namespace Signifyd\Core\Api;

use Signifyd\Core\Connection;
use Signifyd\Core\Exceptions\CaseModelException;
use Signifyd\Core\Exceptions\InvalidClassException;
use Signifyd\Core\Logging;
use Signifyd\Core\Response\CaseResponse;
use Signifyd\Core\Settings;
use Signifyd\Models\CaseModel;
use Signifyd\Models\PaymentUpdate;

/**
 * Class CaseApi
 *
 * @category Signifyd_Fraud_Protection
 * @package  Signifyd\Core
 * @author   Signifyd <info@signifyd.com>
 * @license  See LICENSE.txt for license details.
 * @link     https://www.signifyd.com/
 */
class CaseApi
{
    /**
     * The SDK settings
     *
     * @var Settings The settings object
     */
    public $settings;

    /**
     * The curl connection class
     *
     * @var Connection The connection object
     */
    public $connection;

    public $logger;

    /**
     * CaseApi constructor.
     *
     * @param array $args The settings values
     *
     * @throws \Signifyd\Core\Exceptions\LoggerException
     * @throws \Signifyd\Core\Exceptions\ConnectionException
     */
    public function __construct($args = [])
    {
        if (is_array($args) && !empty($args)) {
            $this->settings = new Settings($args);
        } elseif ($args instanceof Settings) {
            $this->settings = $args;
        } else {
            $this->settings = new Settings([]);
        }

        $this->logger = new Logging($this->settings);
        $this->connection = new Connection($this->settings);
        $this->logger->info('CaseApi initialized');
    }

    /**
     * Create a case in Signifyd
     *
     * @param \Signifyd\Models\CaseModel $case The case data
     *
     * @return bool|\Signifyd\Core\Response
     *
     * @throws CaseModelException
     * @throws InvalidClassException
     */
    public function createCase($case)
    {
        $this->logger->info('CreateCase method called');
        if (is_array($case)) {
            $case = new \Signifyd\Models\CaseModel($case);
            //$valid = $case->validate();
            $valid = true;
            if (false === $valid) {
                $this->logger->error('Case not valid after array init');
                return false;
            }
        } elseif ($case instanceof CaseModel) {
            $case->validate();
//            $valid = $case->validate();
            $valid = true;
            if (false === $valid) {
                $this->logger->error('Case not valid after object init');
                return false;
            }
        } else {
            $this->logger->error('Invalid parameter for create case');
            throw new CaseModelException(
                'Invalid parameter for create case'
            );
        }

        $this->logger->info(
            'Calling connection call Api with case: ' . $case->toJson()
        );
        $response = $this->connection->callApi('cases', $case->toJson(), 'post', 'case');

        return $response;
    }

    /**
     * Getting the case from Signifyd
     *
     * @param $caseId
     *
     * @return CaseResponse
     *
     * @throws InvalidClassException
     */
    public function getCase($caseId)
    {
        $this->logger->info('Get case method called');
        if (false === is_numeric($caseId)) {
            $this->logger->error('Invalid case id for get case' . $caseId);
            $caseResponse = new CaseResponse();
            $caseResponse->setIsError(true);
            $caseResponse->setErrorMessage('Invalid case id');
            return $caseResponse;
        }

        $this->logger->info(
            'Calling connection call get case api with caseId: ' . $caseId
        );

        $endpoint = 'cases' . $caseId;
        $response = $this->connection->callApi($endpoint);

        return $response;
    }

    /**
     * Close a case in Signifyd
     *
     * @param $caseId
     *
     * @return CaseResponse
     *
     * @throws InvalidClassException
     */
    public function closeCase($caseId)
    {
        $this->logger->info('Close case method called');
        if (false === is_numeric($caseId)) {
            $this->logger->error('Invalid case id for get case' . $caseId);
            $caseResponse = new CaseResponse();
            $caseResponse->setIsError(true);
            $caseResponse->setErrorMessage('Invalid case id');
            return $caseResponse;
        }

        // TODO need to move this to a model ???
        $caseSend = ['status' => 'DISMISSED'];
        $this->logger->info(
            'Calling connection call get case api with caseId: ' . $caseId
        );

        $endpoint = 'cases' . $caseId;
        $payload = json_encode($caseSend);
        $response = $this->connection->callApi($endpoint, $payload, 'put');

        return $response;
    }

    /**
     * Update payment in Signifyd
     *
     * @param \Signifyd\Models\PaymentUpdate $paymentUpdate The update case data
     *
     * @return \Signifyd\Core\Response\CaseResponse
     *
     * @throws InvalidClassException
     * @throws CaseModelException
     */
    public function updatePayment($paymentUpdate)
    {
        $this->logger->info('Get case method called');
        if (is_array($paymentUpdate)) {
            $paymentUpdate = new \Signifyd\Models\PaymentUpdate($paymentUpdate);
            //$valid = $paymentUpdate->validate();
            $valid = true;
            if (false === $valid) {
                $this->logger->error('Case not valid after array init');
                $caseResponse = new CaseResponse();
                $caseResponse->setIsError(true);
                $caseResponse->setErrorMessage('Case not valid after array init');
                return $caseResponse;
            }
        } elseif ($paymentUpdate instanceof PaymentUpdate) {
//            $valid = $paymentUpdate->validate();
            $valid = true;
            if (false === $valid) {
                $this->logger->error('Case not valid after object init');
                $caseResponse = new CaseResponse();
                $caseResponse->setIsError(true);
                $caseResponse->setErrorMessage('Case not valid after object init');
                return $caseResponse;
            }
        } else {
            $this->logger->error('Invalid parameter for payment update');
            throw new CaseModelException(
                'Invalid parameter for payment update'
            );
        }

        $this->logger->info(
            'Calling connection call payment update api with payment: ' . $paymentUpdate->toJson()
        );

        $endpoint = 'cases' . $paymentUpdate->getCaseId();
        unset($paymentUpdate->caseId);
        $response = $this->connection->callApi($endpoint, $paymentUpdate->toJson(), 'put');

        return $response;
    }

    /**
     * Update an investigation in Signifyd
     *
     * @param $caseId
     * @param $investigationUpdate
     *
     * @return \Signifyd\Core\Response
     *
     * @throws InvalidClassException
     */
    public function updateInvestigationLabel($caseId, $investigationUpdate)
    {
        $this->logger->info('Update investigation label method called');
        if (false === is_numeric($caseId)) {
            $this->logger->error('Invalid case id for update investigation label' . $caseId);
            $caseResponse = new CaseResponse();
            $caseResponse->setIsError(true);
            $caseResponse->setErrorMessage('Invalid case id');
            return $caseResponse;
        }

        if (false === is_numeric($caseId)) {
            $this->logger->error('Invalid case id for update investigation label' . $caseId);
            $caseResponse = new CaseResponse();
            $caseResponse->setIsError(true);
            $caseResponse->setErrorMessage('Invalid case id');
            return $caseResponse;
        }

        // TODO need to move this to a model ???
        $caseSend = ['reviewDisposition' => $investigationUpdate];
        $this->logger->info(
            'Calling connection call get case api with caseId: ' . $caseId
        );

        $endpoint = 'cases' . $caseId;
        $payload = json_encode($caseSend);
        $response = $this->connection->callApi($endpoint, $payload, 'put');

        return $response;
    }
}