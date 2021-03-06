<?php

namespace AppBundle\Controller;

use AppBundle\Document\Context;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    private $error = null;

    protected function getManager() {
        return $this->get("doctrine_mongodb")->getManager();
    }

    /**
     * Gets the repository for a class.
     *
     * @param string $repo
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepo($repo) {
        return $this->getManager()->getRepository($repo);
    }

    protected function startStatisticsCounter() {
        $this->get("app.statistics_service")->startStatisticsCounter();
    }

    protected function stopCounterAndLogStatistics($operation, $context, $data = null, $dyadicContext = null) {
        $this->get("app.statistics_service")->stopCounterAndLogStatistics($operation, $context, $data, $dyadicContext);
    }

    protected function renderError($error, $activeMenu)
    {
        return $this->render("@App/Default/error.html.twig", array(
            'error' => $error,
            'activeMenu' => $activeMenu,
        ));
    }

    protected function renderFoundError($activeMenu)
    {
        return $this->renderError($this->error, $activeMenu);
    }

    /**
     * @param $context Context
     * @param $validations array
     * @return Response
     * @throws InternalErrorException
     */
    protected function isValidContext($context, $validations)
    {
        $contextService = $this->get("app.context_service");

        foreach ($validations as $validation) {
            switch ($validation) {
                case "not null":
                    if ($context == null) {
                        $this->error = "No context was found with the given id.";
                        return false;
                    }
                    break;
                case "can view":
                    if (!$context->getIsPublic() && $context->getUser() != $this->getUser()) {
                        $this->error = "You don't have the permissions to view this context.";
                        return false;
                    }
                    break;
                case "is own":
                    if ($context->getUser() != $this->getUser()) {
                        $this->error = "You don't have permissions to edit this context.";
                        return false;
                    }
                    break;
                case "can compute concepts":
                    if (!$contextService->canComputeConcepts($context)) {
                        $this->error = "This context is too large to generate it's concepts.";
                        return false;
                    }
                    break;
                case "has concepts":
                    if (empty($context->getConcepts())) {
                        $this->error = "This context doesn't have its concepts generated. Please generate them first.";
                        return false;
                    }
                    break;
                case "not has concepts":
                    if (!empty($context->getConcepts())) {
                        $this->error = "This context has its concepts generated.";
                        return false;
                    }
                    break;
                case "can compute concept lattice":
                    if (!$contextService->canComputeConceptLattice($context)) {
                        $this->error = "This context is too large to generate it's concept lattice.";
                        return false;
                    }
                    break;
                case "has concept lattice":
                    if ($context->getConceptLattice() == null) {
                        $this->error = "This context doesn't have a concept lattice generated. Please generate it first.";
                        return false;
                    }
                    break;
                case "is dyadic":
                    if ($context->getDimCount() != 2) {
                        $this->error = "This context is not a dyadic context.";
                        return false;
                    }
                    break;
                case "is triadic":
                    if ($context->getDimCount() != 3) {
                        $this->error = "This context is not a triadic context.";
                        return false;
                    }
                    break;
                default:
                    throw new InternalErrorException();
            }
        }

        return true;
    }

    public function clearBreadcrumb() {
        $this->get("session")->set("breadcrumb", array());
    }

    public function updateBreadcrumb($level, $text, $route) {
        $breadcrumb = $this->get("session")->get("breadcrumb", array());
        // TODO: Finish the breadcrumb.
    }
}
