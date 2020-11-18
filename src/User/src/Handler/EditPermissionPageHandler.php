<?php
declare(strict_types=1);

namespace User\Handler;

use App\Traits\CsrfTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\SessionCsrfGuard;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Entity\Permission;
use User\Form\PermissionForm;
use User\Service\PermissionManager;
use function gettype;
use function is_array;
use function sprintf;

class EditPermissionPageHandler implements RequestHandlerInterface
{
    use CsrfTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PermissionManager */
    private $permissionManager;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var UrlHelper */
    private $urlHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        PermissionManager $permissionManager,
        TemplateRendererInterface $renderer,
        UrlHelper $helper
    ) {
        $this->entityManager     = $entityManager;
        $this->permissionManager = $permissionManager;
        $this->renderer          = $renderer;
        $this->urlHelper         = $helper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id', -1);
        if ($id < 1) {
            return new HtmlResponse($this->renderer->render('error::404'), 404);
        }

        /** @var Permission $permission */
        $permission = $this->entityManager->getRepository(Permission::class)
            ->find($id);

        if ($permission == null) {
            return new HtmlResponse($this->renderer->render('error::404'), 404);
        }

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        /** @var SessionCsrfGuard $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $token = $this->getToken($session, $guard);

        $form = new PermissionForm($guard, 'update', $this->entityManager, $permission);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                // get filtered and validated form data
                $data = $form->getData();
                if (! is_array($data)) {
                    throw new Exception(sprintf('Expected array return type, got %s', gettype($data)));
                }

                $this->permissionManager->updatePermission($permission, $data);
                return new RedirectResponse($this->urlHelper->generate('admin.permission.list'));
            }

            $token = $this->getToken($session, $guard);
        } else {
            $form->setData([
                'name'        => $permission->getName(),
                'description' => $permission->getDescription(),
            ]);
        }

        return new HtmlResponse($this->renderer->render('permission::edit', [
            'token'      => $token,
            'form'       => $form,
            'permission' => $permission,
        ]));
    }
}
