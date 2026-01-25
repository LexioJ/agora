<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Controller;

use Closure;
use OCA\Agora\Exceptions\Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-api
 * @psalm-import-type HttpStatusCode from \OCA\Agora\Types
 */
class BaseController extends Controller
{
    protected ?LoggerInterface $logger = null;

    public function __construct(
        string $appName,
        IRequest $request,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($appName, $request);
        $this->logger = $logger;
    }

    /**
     * response
  *
     * @param       Closure $callback Callback function
     * @psalm-param HttpStatusCode $successStatus HTTP status code for success
     */
    #[NoAdminRequired]
    protected function response(
        Closure $callback,
        int $successStatus = Http::STATUS_OK,
    ): JSONResponse {
        try {
            return new JSONResponse($callback(), $successStatus);
        } catch (Exception $e) {

            if ($e->getStatus() === Http::STATUS_NOT_MODIFIED) {
                return new JSONResponse(statusCode: Http::STATUS_NOT_MODIFIED);
            }

            /**
       * @var HttpStatusCode $status
*/
            $status = $e->getStatus();
            return new JSONResponse(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            // Log unexpected exceptions
            if ($this->logger) {
                $this->logger->error(
                    'Unexpected exception in controller: ' . $e->getMessage(),
                    [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            return new JSONResponse(
                ['message' => 'Internal server error: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

}
