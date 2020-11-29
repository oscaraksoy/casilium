<?php

declare(strict_types=1);

namespace Organisation\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Organisation\Entity\Domain;
use Organisation\Entity\Organisation;
use Organisation\Entity\OrganisationInterface;
use Organisation\Exception\OrganisationExistsException;
use Organisation\Exception\OrganisationSitesExistException;
use Organisation\Hydrator\OrganisationHydrator;
use OrganisationSite\Service\SiteManager;
use function in_array;

class OrganisationManager
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SiteManager */
    protected $siteManager;

    public function __construct(EntityManagerInterface $entityManager, SiteManager $siteManager)
    {
        $this->entityManager = $entityManager;
        $this->siteManager   = $siteManager;
    }

    public function createOrganisation(OrganisationInterface $organisation): ?OrganisationInterface
    {
        // if organisation exists, throw an exception
        if ($result = $this->findOrganisationByName($organisation->getName())) {
            throw OrganisationExistsException::whenCreating($result->getName());
        }

        // save the organisation
        $this->entityManager->persist($organisation);
        $this->entityManager->flush();

        // return the newly created organisationId
        return $organisation;
    }

    public function createOrganisationFromArray(array $data): ?OrganisationInterface
    {
        $hydrator     = new OrganisationHydrator();
        $organisation = $hydrator->hydrate($data, new Organisation());

        return $this->createOrganisation($organisation);
    }

    public function findOrganisationByName(string $name): ?Organisation
    {
        // find organisation in repository
        $organisation = $this->entityManager->getRepository(Organisation::class)
            ->findOneBy(['name' => $name]);

        // return organisation
        if ($organisation instanceof Organisation) {
            return $organisation;
        }

        // or return nothing
        return null;
    }

    public function findOrganisationById(int $id): ?Organisation
    {
        return $this->entityManager->getRepository(Organisation::class)
            ->findOneBy(['id' => $id]);
    }

    public function findOrganisationByUuid(string $uuid): ?Organisation
    {
        return $this->entityManager->getRepository(Organisation::class)
            ->findOneByUuid($uuid);
    }

    /**
     * Update Organisation
     *
     * Update existing organisation, first update organisation details then remove any domains no longer required
     * and finally add the new domains.
     *
     * @param int $id organisation to update
     * @param array $data data to populate
     * @throws Exception
     */
    public function updateOrganisation(int $id, array $data): void
    {
        // clear pending doctrine operations
        $this->entityManager->clear();

        /** @var Organisation $organisation */
        $organisation = $this->entityManager->getRepository(Organisation::class)->find($id);
        $organisation->setName($data['name']);
        $organisation->setIsActive($data['is_active']);

        $domains = [];
        foreach ($organisation->getDomains() as $domain) {
            // if domain is not in list then remove
            if (! in_array($domain->getName(), $data['domain'])) {
                // remove domain
                $organisation->removeDomain($domain);
            } else {
                // otherwise add to list of domains to keep
                $domains[] = $domain->getName();
            }
        }

        // loop through domains passed
        foreach ($data['domain'] as $domain) {
            // if domain passed is not in current list
            if (! in_array($domain, $domains)) {
                // it's a new one so we need to add it.
                $newDomain = new Domain();
                $newDomain->setName($domain);
                $newDomain->setOrganisation($organisation);
                $organisation->addDomain($newDomain);
            }
        }

        // update modification date and save
        $organisation->setModified();
        $this->entityManager->flush();
    }

    public function removeOrganisationDomains(int $id): void
    {
        $domains = $this->entityManager->getRepository(Domain::class)
            ->findBy(['organisation' => $id]);

        foreach ($domains as $domain) {
            $this->entityManager->remove($domain);
        }
    }

    /**
     * Fetch all organisations
     *
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->entityManager->getRepository(Organisation::class)->findAll();
    }

    public function delete(Organisation $organisation): void
    {
        // check if organisation has sites before deleting.
        if ($sites = $this->siteManager->fetchSitesByOrganisationId($organisation->getId())) {
            throw OrganisationSitesExistException::whenDeleting($organisation->getName());
        }

        // remove the organisation
        $this->entityManager->remove($organisation);
        $this->entityManager->flush();
    }
}
