<?php /** @noinspection PhpUnused */

namespace Bitter\AiAssistant\API\V1;

use Bitter\AiAssistant\ContentGenerator\Service;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Http\Request;
use Concrete\Core\Application\EditResponse;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\ResponseFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ContentGenerator
{
    protected EntityManagerInterface $entityManager;
    protected ResponseFactoryInterface $responseFactory;
    protected Connection $db;
    protected Request $request;
    protected Service $contentGeneratorService;

    public function __construct(
        EntityManagerInterface   $entityManager,
        ResponseFactoryInterface $responseFactory,
        Request                  $request,
        Connection               $db,
        Service                  $contentGeneratorService
    )
    {
        $this->entityManager = $entityManager;
        $this->responseFactory = $responseFactory;
        $this->request = $request;
        $this->db = $db;
        $this->contentGeneratorService = $contentGeneratorService;
    }

    public function generateText(): Response
    {
        $editResponse = new EditResponse();
        $errorList = new ErrorList();

        $input = $this->request->request->get("input");

        $output = null;

        try {
            $output = $this->contentGeneratorService->generateText($input);
        } catch (Exception $e) {
            $errorList->add($e->getMessage());
        }

        $editResponse->setAdditionalDataAttribute("output", $output);

        $editResponse->setError($errorList);

        return $this->responseFactory->json($editResponse);
    }
}