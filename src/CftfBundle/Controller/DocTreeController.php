<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Form\Type\LsDocListType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Util\Compare;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends Controller
{
    /**
     * PW: legacy doctree viewAction; we can delete this fn once the new version has been tested
     *
     * @Route("/docx/{id}.{_format}", name="doc_tree_viewx", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/docx/{id}/av.{_format}", name="doc_tree_view_avx", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/docx/{id}/{assocGroup}.{_format}", name="doc_tree_view_agx", defaults={"_format"="html", "lsItemId"=null})
     * @Method({"GET"})
     * @Template()
     */
    public function viewxAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null, $assocGroup = null)
    {

        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

        $em = $this->getDoctrine()->getManager();

        // Get all association groups (for all documents); 
        // we need groups for other documents if/when we show a document on the right side
        $lsDefAssociationGroupings = $em->getRepository('CftfBundle:LsDefAssociationGrouping')->findAll();
        
        // Get a list of all associations and process them...
        $lsAssociations = $em->getRepository('CftfBundle:LsAssociation')->findBy(['lsDoc'=>$lsDoc]);
        $assocItems = array();
        foreach ($lsAssociations as $assoc) {
            // for each assoc, we'll decide whether or not we need to include info about the origin and/or destination item

            // if the assoc has a destination in the current SALT instance...
            if (!empty($assoc->getDestinationLsItem())) {
                // then if the destination item's document isn't the current document...
                if ($assoc->getDestinationLsItem()->getLsDoc()->getId() != $lsDoc->getId()) {
                    // we need to include info about the item
                    $assocItems[$assoc->getDestinationLsItem()->getId()] = $assoc->getDestinationLsItem();
                }
            }

            // if the assoc has an origin in the current SALT instance (it almost always will)...
            if (!empty($assoc->getOriginLsItem())) {
                // then if the Origin item's document isn't the current document...
                if ($assoc->getOriginLsItem()->getLsDoc()->getId() != $lsDoc->getId()) {
                    // we need to include info about the item
                    $assocItems[$assoc->getOriginLsItem()->getId()] = $assoc->getOriginLsItem();
                }
            }
        }
        
        // get list of all documents
        $resultlsDocs = $em->getRepository('CftfBundle:LsDoc')->findBy([], ['creator'=>'ASC', 'title'=>'ASC', 'adoptionStatus'=>'ASC']);
        $lsDocs = [];
        $authChecker = $this->get('security.authorization_checker');
        foreach ($resultlsDocs as $doc) {
            if ($authChecker->isGranted('view', $doc)) {
                $lsDocs[] = $doc;
            }
        }

        return [
            'lsDoc' => $lsDoc,
            'lsItemId' => $lsItemId,
            'assocGroup' => $assocGroup,
            'docList' => $form->createView(),
            'assocGroups' => $lsDefAssociationGroupings,
            'lsAssociations' => $lsAssociations,
            'assocItems' => $assocItems,
            'lsDocs' => $lsDocs
        ];
    }

///////////////////////////////////////////////
    /**
     * @Route("/doc/{id}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{id}/av.{_format}", name="doc_tree_view_av", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{id}/{assocGroup}.{_format}", name="doc_tree_view_ag", defaults={"_format"="html", "lsItemId"=null})
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null, $assocGroup = null)
    {

        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

        $em = $this->getDoctrine()->getManager();

        // Get all association groups (for all documents); 
        // we need groups for other documents if/when we show a document on the right side
        $lsDefAssociationGroupings = $em->getRepository('CftfBundle:LsDefAssociationGrouping')->findAll();
 
        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
        }
       
        // Get a list of all associations and process them...
        $lsAssociations = $em->getRepository('CftfBundle:LsAssociation')->findBy(['lsDoc'=>$lsDoc]);
        $assocItems = array();
        foreach ($lsAssociations as $assoc) {
            // for each assoc, we'll decide whether or not we need to include info about the origin and/or destination item

            // if the assoc has a destination in the current SALT instance...
            if (!empty($assoc->getDestinationLsItem())) {
                // then if the destination item's document isn't the current document...
                if ($assoc->getDestinationLsItem()->getLsDoc()->getId() != $lsDoc->getId()) {
                    // we need to include info about the item
                    $assocItems[$assoc->getDestinationLsItem()->getId()] = $assoc->getDestinationLsItem();
                }
            }

            // if the assoc has an origin in the current SALT instance (it almost always will)...
            if (!empty($assoc->getOriginLsItem())) {
                // then if the Origin item's document isn't the current document...
                if ($assoc->getOriginLsItem()->getLsDoc()->getId() != $lsDoc->getId()) {
                    // we need to include info about the item
                    $assocItems[$assoc->getOriginLsItem()->getId()] = $assoc->getOriginLsItem();
                }
            }
        }
        
        // get list of all documents
        $resultlsDocs = $em->getRepository('CftfBundle:LsDoc')->findBy([], ['creator'=>'ASC', 'title'=>'ASC', 'adoptionStatus'=>'ASC']);
        $lsDocs = [];
        $authChecker = $this->get('security.authorization_checker');
        foreach ($resultlsDocs as $doc) {
            if ($authChecker->isGranted('view', $doc)) {
                $lsDocs[] = $doc;
            }
        }

        return [
            'lsDoc' => $lsDoc,
            'lsItemId' => $lsItemId,
            'assocGroup' => $assocGroup,
            'docList' => $form->createView(),
            'assocTypes' => $assocTypes,
            'inverseAssocTypes' => $inverseAssocTypes,
            'assocGroups' => $lsDefAssociationGroupings,
            'lsAssociations' => $lsAssociations,
            'assocItems' => $assocItems,
            'lsDocs' => $lsDocs
        ];
    }

///////////////////////////////////////////////

    /**
     * Export a CFPackage in a special json format designed for efficiently loading the package's data to the OpenSALT doctree client
     *
     * @Route("/docexport/{id}.json", name="doctree_cfpackage_export")
     * @Method("GET")
     */
    public function exportAction(LsDoc $lsDoc)
    {
        $items = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAllItems($lsDoc);
        $associations = $this->getDoctrine()->getRepository('CftfBundle:LsAssociation')->findBy(['lsDoc'=>$lsDoc]);
        $assocGroups = $this->getDoctrine()->getRepository('CftfBundle:LsDefAssociationGrouping')->findBy(['lsDoc'=>$lsDoc]);
        $docAttributes = [
            "baseDoc" => $lsDoc->getAttribute("baseDoc"),
            "externalDocs" => $lsDoc->getExternalDocs()
        ];

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $lsDoc,
            'docAttributes' => $docAttributes,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $lsDoc->getSubjects(),
            'concepts' => [],
            'licences' => [],
            'assocGroups' => $assocGroups,
        ];
        $response = new Response($this->renderView("CftfBundle:DocTree:export.json.twig", $arr));
        $response->headers->set('Content-Type', 'text/json');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Retrieve a CFPackage from the given document identifier, then use exportAction to export it
     *
     * @Route("/retrievedocument/{id}", name="doctree_retrieve_document")
     * @Method("GET")
     */
    public function retrieveDocumentAction(Request $request, LsDoc $lsDoc)
    {
        // $request could contain an id...
        if ($id = $request->query->get('id')) {
            // in this case it has to be a document on this OpenSALT instantiation
            $newDoc = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findOneBy(['id'=>$id]);
            if (empty($newDoc)) {
                // if document not found, error
                return new Response('Document not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->exportAction($newDoc);
        
        // or an identifier...
        } else if ($identifier = $request->query->get('identifier')) {
            // first see if it's referencing a document on this OpenSALT instantiation
            $newDoc = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findOneBy(['identifier'=>$identifier]);
            if (!empty($newDoc)) {
                return $this->exportAction($newDoc);
            }

            // otherwise look in this doc's externalDocs
            // We could store, and check here, a global table of external documents that we could index by identifiers, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
            $externalDocs = $lsDoc->getExternalDocs();
            if (!empty($externalDocs[$identifier])) {
                // if we found it, load it, noting that we don't have to save a record of it in externalDocs (since it's already there)
                return $this->exportExternalDocument($externalDocs[$identifier]["url"], null);
            }
            
            // if not found in externalDocs, error
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        
        // or a url...
        } else if ($url = $request->query->get('url')) {
            // try to load the url, noting that we shoudl save a record of it in externalDocs if found
            return $this->exportExternalDocument($url, $lsDoc);
        }
        
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }
    
    protected function exportExternalDocument($url, $lsDoc) {
        // We could store, and check here, a global table of external documents that we could index by urls, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
        // TODO: We could "cache" external documents by simply saving a copy of the document files on this OpenSALT server. This way, if the document ever becomes unavailable from the external server, we would still be able to reference it. We could then decide whether to try to refresh the "cache" every time the file is accessed; or we could refresh if the cached version is more than 30 (or 5, or 60, or 1440, etc.) minutes old
        // PW: the methods used below may not be the best/most elegant way to check for the existence of and/or load the external file...

        // first check to see if this url returns a valid document (function taken from notes of php file_exists)
        $file_headers = @get_headers($url);
        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        }
        
        // file exists, so get it
        $s = file_get_contents($url);
        if (!empty($s)) {
            // if $lsDoc is not empty, get the document's identifier and title and save to the $lsDoc's externalDocs
            if (!empty($lsDoc)) {
                // This might not be the most elegant way to get  way to get the doc's identifier and id, but it should work
                $identifier = "";
                if (preg_match("/\"identifier\"\s*:\s*\"(.+?)\"/", $s, $matches)) {
                    $identifier = $matches[1];
                }
                $title = "";
                if (preg_match("/\"title\"\s*:\s*\"([\s\S]+?)\"/", $s, $matches)) {
                    $title = $matches[1];
                }
                
                // if we found the identifier and title, save the ad
                if (!empty($identifier) && !empty($title)) {
                    // see if the doc is already there; if so, we don't want to change the "autoLoad" parameter, but we should still update the title/url if necessary
                    $externalDocs = $lsDoc->getExternalDocs();
                    if (!empty($externalDocs[$identifier])) {
                        $autoLoad = $externalDocs[$identifier]["autoLoad"];
                    } else {
                        // if it's a newly-associated doc, assume here that it does not need to be "autoloaded"; that will be changed if/when we add an association with an item in the doc
                        $autoLoad = "false";
                    }

                    // if this is a new doc or anything has changed, save it
                    if (empty($externalDocs[$identifier]) || $externalDocs[$identifier]["autoLoad"] != $autoLoad || $externalDocs[$identifier]["url"] != $url || $externalDocs[$identifier]["title"] != $title) {
                        $lsDoc->addExternalDoc($identifier, $autoLoad, $url, $title);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($lsDoc);
                        $em->flush();
                    }
                }
            }
            
            // now return the file
            $response = new Response($s);
            $response->headers->set('Content-Type', 'text/json');
            $response->headers->set('Pragma', 'no-cache');
        
            return $response;
        }
        
        // if we get to here, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        
        // example urls:
        // http://127.0.0.1:3000/app_dev.php/uri/731cf3e4-43a2-4aa0-b2a7-87a49dac5374.json
        // https://salt-staging.edplancms.com/uri/b821b70d-d46c-519b-b5cc-ca2260fc31f8.json
        // https://salt-staging.edplancms.com/cfpackage/doc/11/export
    }
    
    
    /**
     * @Route("/item/{id}/details", name="doc_tree_item_details")
     * @Method("GET")
     * @Template()
     *
     * Note that this must come before viewItemAction for the url mapping to work properly.
     *
     * @param \CftfBundle\Entity\LsItem $lsItem
     *
     * @return array
     */
    public function treeItemDetailsAction(LsItem $lsItem)
    {
        return ['lsItem'=>$lsItem];
    }


    /**
     * PW: legacy viewItemAction; we can delete this fn once the new version has been tested
     *
     * @Route("/itemx/{id}.{_format}", name="doc_tree_item_viewx", defaults={"_format"="html"})
     * @Route("/itemx/{id}/{assocGroup}.{_format}", name="doc_tree_item_view_agx", defaults={"_format"="html"})
     * @Method({"GET"})
     *
     * @param LsItem $lsItem
     * @param string $assocGroup
     * @param string $_format
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewItemActionx(LsItem $lsItem, $assocGroup = null, $_format = 'html')
    {
        return $this->forward('CftfBundle:DocTree:viewx', ['lsDoc' => $lsItem->getLsDoc(), 'html', 'lsItemId' => $lsItem->getid(), 'assocGroup' => $assocGroup]);
    }


    /**
     * @Route("/item/{id}.{_format}", name="doc_tree_item_view", defaults={"_format"="html"})
     * @Route("/item/{id}/{assocGroup}.{_format}", name="doc_tree_item_view_ag", defaults={"_format"="html"})
     * @Method({"GET"})
     *
     * @param LsItem $lsItem
     * @param string $assocGroup
     * @param string $_format
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewItemAction(LsItem $lsItem, $assocGroup = null, $_format = 'html')
    {
        return $this->forward('CftfBundle:DocTree:view', ['lsDoc' => $lsItem->getLsDoc(), 'html', 'lsItemId' => $lsItem->getid(), 'assocGroup' => $assocGroup]);
    }



    /**
     * @Route("/render/{id}.{_format}", defaults={"_format"="html"}, name="doctree_render_document")
     * @Method("GET")
     * @Template()
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     *
     * PW: this is similar to the renderDocument function in the Editor directory, but different enough that I think it deserves a separate controller/view
     */
    public function renderDocumentAction(LsDoc $lsDoc, $_format = 'html')
    {
        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');

        $items = $repo->findAllChildrenArray($lsDoc);
        $haveParents = $repo->findAllItemsWithParentsArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);
        $parentsElsewhere = [];

        $orphaned = $items;
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }

        foreach ($orphaned as $orphan) {
            foreach ($orphan['associations'] as $association) {
                if (LsAssociation::CHILD_OF === $association['type']) {
                    $parentsElsewhere[] = $orphan;
                    unset($orphaned[$orphan['id']]);
                }
            }
        }


        Compare::sortArrayByFields($orphaned, ['rank', 'listEnumInSource', 'humanCodingScheme']);

        return [
            'topItemIds' => $topChildren,
            'lsDoc' => $lsDoc,
            'items' => $items,
            'parentsElsewhere' => $parentsElsewhere,
            'orphaned' => $orphaned,
        ];
    }

    /**
     * Deletes a LsItem entity, from the tree view.
     *
     * @Route("/item/{id}/delete/{includingChildren}", name="lsitem_tree_delete", defaults={"includingChildren" = 0})
     * @Method("POST")
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     * @param int $includingChildren
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request, LsItem $lsItem, $includingChildren = 0)
    {
        $ajax = false;
        if ($request->isXmlHttpRequest()) {
            $ajax = true;
        }
        $lsDocId = $lsItem->getLsDoc()->getId();

        $em = $this->getDoctrine()->getManager();

        if ($includingChildren) {
            $em->getRepository(LsItem::class)->removeItemAndChildren($lsItem);
            $em->flush();
        } else {
            $em->getRepository(LsItem::class)->removeItem($lsItem);
            $em->flush();
        }

        if ($ajax) {
            return new Response($this->generateUrl('doc_tree_view', ['id' => $lsDocId]), Response::HTTP_ACCEPTED);
        } else {
            return $this->redirectToRoute('doc_tree_view', ['id' => $lsDocId]);
        }
    }

    /**
     * Updates a set of items in the document from the tree view
     * Reorders are done by updating the listEnum fields of the items
     * This also does copies, of either single items or folders.
     * If we do a copy, the service returns an array of trees with the copied lsItemIds.
     * For other operations, we return an empty array.
     *
     * @Route("/doc/{id}/updateitems.{_format}", name="doctree_update_items")
     * @Method("POST")
     * @Security("is_granted('edit', lsDoc)")
     * @Template()
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     *
     * @return array
     */
    public function updateItemsAction(Request $request, LsDoc $lsDoc, $_format = 'json')
    {
        $rv = [];

        $em = $this->getDoctrine()->getManager();
        $assocGroupRepo = $em->getRepository(LsDefAssociationGrouping::class);

        $lsItems = $request->request->get('lsItems');
        foreach ($lsItems as $lsItemId => $updates) {
            $rv[$lsItemId] = [
                'originalKey' => $updates['originalKey'],
            ];

            // set assocGroup if supplied; pass this in when necessary below
            $assocGroup = null;
            if (array_key_exists('assocGroup', $updates)) {
                $assocGroup = $assocGroupRepo->find($updates['assocGroup']);
            }

            $lsItem = $this->getItemForUpdate($lsDoc, $updates, $lsItemId, $assocGroup);

            // return the id and fullStatement of the item, whether it's new or it already existed
            $rv[$lsItemId]['lsItemId'] = $lsItem->getId();
            $rv[$lsItemId]['lsItemIdentifier'] = $lsItem->getIdentifier();
            $rv[$lsItemId]['fullStatement'] = $lsItem->getFullStatement();

            if (array_key_exists('deleteChildOf', $updates)) {
                $this->deleteChildAssociations($lsItem, $updates, $lsItemId, $rv);
            } elseif (array_key_exists('updateChildOf', $updates)) {
                $this->updateChildOfAssociations($lsItem, $updates, $lsItemId, $rv);
            }

            // create new childOf association if specified
            if (array_key_exists('newChildOf', $updates)) {
                $this->addChildOfAssociations($lsItem, $updates, $lsItemId, $rv, $assocGroup);
            }
        }

        // send new lsItem updatedAt??

        $em->flush();

        // get ids for new associations
        foreach ($rv as $lsItemId => $val) {
            if (!empty($rv[$lsItemId]['association'])) {
                $rv[$lsItemId]['assocId'] = $rv[$lsItemId]['association']->getId();
                unset($rv[$lsItemId]['association']);
            }
        }

        return ['returnedItems' => $rv];
    }

    /**
     * Deletes a LsDefAssociationGrouping entity, ajax/treeview version.
     *
     * @Route("/assocgroup/{id}/delete", name="lsdef_association_grouping_tree_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping
     *
     * @return string
     */
    public function deleteAssocGroupAction(Request $request, LsDefAssociationGrouping $lsDefAssociationGrouping)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($lsDefAssociationGrouping);
        $em->flush();

        return new Response('OK', Response::HTTP_ACCEPTED);
    }

    /**
     * Get the item to update, either the original or a copy based on the update array
     *
     * @param LsDoc $lsDoc
     * @param array $updates
     * @param int $lsItemId
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return LsItem
     */
    protected function getItemForUpdate(LsDoc $lsDoc, array $updates, $lsItemId, ?LsDefAssociationGrouping $assocGroup = null): LsItem
    {
        $em = $this->getDoctrine()->getManager();
        $lsItemRepo = $em->getRepository(LsItem::class);

        // copy item if copyFromId is specified
        if (array_key_exists('copyFromId', $updates)) {
            $originalItem = $lsItemRepo->find($updates['copyFromId']);

            $lsItem = $originalItem->copyToLsDoc($lsDoc, $assocGroup);
            // if addCopyToTitle is set, add "Copy of " to fullStatement and abbreviatedStatement
            if (array_key_exists('addCopyToTitle', $updates)) {
                $title = 'Copy of '.$lsItem->getFullStatement();
                $lsItem->setFullStatement($title);
                
                $astmt = $lsItem->getAbbreviatedStatement();
                if (!empty($astmt)) {
                    $astmt = 'Copy of ' . $astmt;
                    $lsItem->setAbbreviatedStatement($astmt);
                }
            }

            $em->persist($lsItem);
            // flush here to generate ID for new lsItem
            $em->flush();

        } else {
            // else get lsItem from the repository
            $lsItem = $lsItemRepo->find($lsItemId);
        }

        return $lsItem;
    }

    /**
     * Remove the appropriate childOf associations for the item based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function deleteChildAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $em = $this->getDoctrine()->getManager();
        $assocRepo = $em->getRepository(LsAssociation::class);

        // delete childOf association if specified
        if ($updates['deleteChildOf']['assocId'] !== 'all') {
            $assocRepo->removeAssociation($assocRepo->find($updates['deleteChildOf']['assocId']));
            $lsItem->setUpdatedAt(new \DateTime());
            $rv[$lsItemId]['deleteChildOf'] = $updates['deleteChildOf']['assocId'];
        } else {
            // if we got "all" for the assocId, it means that we're updating a new item for which the client didn't know an assocId.
            // so in this case, it's OK to just delete any existing childof association and create a new one below
            $assocRepo->removeAllAssociationsOfType($lsItem, LsAssociation::CHILD_OF);
        }
    }

    /**
     * Update the childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function updateChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $em = $this->getDoctrine()->getManager();
        $assocRepo = $em->getRepository(LsAssociation::class);

        // update childOf association if specified
        $assoc = $assocRepo->find($updates['updateChildOf']['assocId']);
        if (!empty($assoc)) {
            // as of now the only thing we update is sequenceNumber
            if (array_key_exists('sequenceNumber', $updates['updateChildOf'])) {
                $assoc->setSequenceNumber($updates['updateChildOf']['sequenceNumber']*1);
            }
            $rv[$lsItemId]['association'] = $assoc;
            $rv[$lsItemId]['sequenceNumber'] = $updates['updateChildOf']['sequenceNumber'];
        }
        $lsItem->setUpdatedAt(new \DateTime());
    }

    /**
     * Add new childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function addChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv, ?LsDefAssociationGrouping $assocGroup = null): void
    {
        $em = $this->getDoctrine()->getManager();

        // parent could be a doc or item
        if ($updates['newChildOf']['parentType'] === 'item') {
            $lsItemRepo = $em->getRepository(LsItem::class);
            $parentItem = $lsItemRepo->find($updates['newChildOf']['parentId']);
        } else {
            $docRepo = $em->getRepository(LsDoc::class);
            $parentItem = $docRepo->find($updates['newChildOf']['parentId']);
        }
        $rv[$lsItemId]['association'] = $lsItem->addParent($parentItem, $updates['newChildOf']['sequenceNumber'], $assocGroup);
        $lsItem->setUpdatedAt(new \DateTime());

        $rv[$lsItemId]['sequenceNumber'] = $updates['newChildOf']['sequenceNumber'];
    }
}
