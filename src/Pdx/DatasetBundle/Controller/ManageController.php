<?php

namespace Pdx\DatasetBundle\Controller;


use League\Flysystem\FileExistsException;
use Pdx\Csv\CsvHandler;
use Pdx\DatasetBundle\Entity\DataSet;
use Pdx\DatasetBundle\Form\DataSetMapType;
use Pdx\DatasetBundle\Form\DataSetType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ManageController
 * @Route("/manage/dataset")
 */
class ManageController extends Controller
{

    /**
     * Returns the entityName
     */
    protected function getEntityName()
    {
        return 'Pdx\DatasetBundle\Entity\DataSet';
    }

    /**
     * Returns the Entity
     *
     * @return |void
     */
    protected function getEntity()
    {
        return new DataSet();
    }

    /**
     * Returns the FormType
     *
     * @return string
     */
    protected function getEntityType()
    {
        return 'Pdx\DatasetBundle\Form\DataSetType';
    }

    /**
     * Explanation page
     *
     * @Route("/info", name="manage-dataset-info")
     * @Method({"GET"})
     */
    public function infoAction()
    {
        return $this->render('PdxDatasetBundle:Manage:info.html.twig', [

        ]);
    }

    /**
     * List a users DataSets
     *
     * @Route("/", name="manage-dataset-index")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $rows = $this->getDoctrine()
            ->getRepository($this->getEntityName())
            ->findBy(['creator' => $this->getUser()]
            );

        return $this->render('PdxDatasetBundle:Manage:index.html.twig', [
            'datasets' => $rows
        ]);
    }


    /**
     * Displays the form to create a new set
     *
     * @Route("/new", name="manage-dataset-new")
     * @Method({"GET"})
     */
    public function newAction()
    {
        $form = $this->createForm(
            'Pdx\DatasetBundle\Form\NewDataSetType',
            $this->getEntity()
        );

        return $this->render(
            'PdxDatasetBundle:Manage:new.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Creates a new set.
     *
     * @Route("/new")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $entity = $this->getEntity();
        $form = $this->createForm(
            'Pdx\DatasetBundle\Form\NewDataSetType',
            $entity
        );

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $form->getData();

            // validation on file names since that is not working the default way
            /** @var UploadedFile $file */
            $file = $entity->getCsvFile();
            if ($file instanceof UploadedFile) {
                if (! preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $file->getClientOriginalName())) {
                    $error = new FormError("De bestandsnaam mag geen spaties bevatten. Alleen lettters en cijfers.");
                    $form->get('csvFile')->addError($error);
                    return $this->render(
                        'PdxDatasetBundle:Manage:new.html.twig', [
                            'form' => $form->createView(),
                        ]
                    );
                }
            }

            try {
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Opgeslagen!'
                );

                return $this->redirect($this->generateUrl('manage-dataset-preview', ['id' => $entity->getId()]));
            } catch (FileExistsException $e) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    'Sorry, dat bestand bestaat al. Geef je bestand s.v.p. een andere naam en upload opnieuw.'
                );
            }

        }

        return $this->render('PdxDatasetBundle:Manage:new.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Preview the uploaded csv to check whether the columns could be retrieved correctly
     *
     * @Route("/preview/{id}", name="manage-dataset-preview", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function previewAction($id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        /** @var CsvHandler $handler */
        $handler = $this->get('pdx_csv.handler');
        if (! $handler->isUTF8($entity->getCsvName())) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Sorry, je CSV is niet in UTF-8 formaat. Converteer het bestand naar utf-8 op je computer en upload s.v.p. opnieuw. '
            );

            return $this->redirect($this->generateUrl('manage-dataset-edit', ['id' => $entity->getId()]));
        }

        $html = $handler->convertCsv2Html($entity->getCsvName(), 20);

        return $this->render('PdxDatasetBundle:Manage:preview.html.twig', [
                'dataset' => $entity,
                'html'    => $html
            ]
        );
    }

    /**
     * Save the mapped columns after an ok by the user.
     *
     * @Route("/save-columns/{id}", name="manage-dataset-save-columns", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function saveColumnsAction($id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        $csvColumns = $this->get('pdx_csv.handler')->getColumns($entity->getCsvName());

        $entity->setColumns($csvColumns);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('manage-dataset-map', ['id' => $id]);
    }

    /**
     * Displays the form to edit an entity.
     *
     * @Route("/edit/{id}", name="manage-dataset-edit", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function editAction($id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        $form = $this->createForm(
            'Pdx\DatasetBundle\Form\DataSetType',
            $entity
        );

        return $this->render('PdxDatasetBundle:Manage:edit.html.twig', [
                'form'    => $form->createView(),
                'dataset' => $entity,
            ]
        );
    }

    /**
     * Saves the updated dataset details
     *
     * @Route("/update/{id}", name="manage-dataset-update", requirements={"id" = "\d+"})
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        $form = $this->createForm(
            'Pdx\DatasetBundle\Form\DataSetType',
            $entity
        );

        $form->handleRequest($request);
        if ($form->isValid()) {

            //$entity = $form->getData();

            // validation on file names since that is not working the default way
            /** @var UploadedFile $file */
            $file = $entity->getCsvFile();
            if ($file instanceof UploadedFile) {
                if (! preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $file->getClientOriginalName())) {
                    $error = new FormError("De bestandsnaam mag geen spaties bevatten. Alleen lettters en cijfers.");
                    $form->get('csvFile')->addError($error);
                    return $this->render('PdxDatasetBundle:Manage:edit.html.twig', [
                        'form'    => $form->createView(),
                        'dataset' => $entity,
                    ]);
                }
            }

            // werid shit needed to persist the uploaded file
            /** @var UploadedFile $file */
            $file = $entity->getPdfFile();
            if ($file instanceof UploadedFile) {
                $entity->setDescription($entity->getDescription() . ' ');
            }

            try {
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Wijzigingen opgeslagen!'
                );

                return $this->redirect($this->generateUrl('manage-dataset-preview', ['id' => $entity->getId()]));
            } catch (FileExistsException $e) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    'Sorry, dat bestand bestaat al. Geef je bestand s.v.p. een andere naam en upload opnieuw.'
                );
            }

        }

        return $this->render('PdxDatasetBundle:Manage:edit.html.twig', [
                'form'    => $form->createView(),
                'dataset' => $entity,
            ]
        );
    }

    /**
     * Displays the mapping form
     *
     * @Route("/map/{id}", name="manage-dataset-map", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function mappingAction($id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        // convert the db mapping and pass it to the form
        $mapping = (array)$entity->getMapping();
        $mapping['file'] = $entity->getCsvName();
        $mapping['columns'] = $entity->getColumns();

        $mappingForm = $this->createForm(
            'Pdx\DatasetBundle\Form\DataSetMapType',
            $mapping
        );

        return $this->render('PdxDatasetBundle:Manage:mapping.html.twig', [
                'form'    => $mappingForm->createView(),
                'dataset' => $entity
            ]
        );
    }

    /**
     * Saves the mapping
     *
     * @Route("/map/{id}", name="manage-dataset-map-save", requirements={"id" = "\d+"})
     * @Method("POST")
     */
    public function saveMappingAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        $mappingForm = $this->createForm(
            'Pdx\DatasetBundle\Form\DataSetMapType',
            [
                'file'    => $entity->getCsvName(),
                'columns' => $entity->getColumns()
            ]
        );

        $mappingForm->handleRequest($request);
        if ($mappingForm->isValid()) {

            $mapping = $mappingForm->getData();

            $entity->setMapping(json_encode($mapping));
            $entity->setStatus(DataSet::STATUS_MAPPED);

            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Mapping opgeslagen!'
            );

            return $this->redirect($this->generateUrl('manage-dataset-index'));
        }

        return $this->render('PdxDatasetBundle:Manage:mapping.html.twig', [
                'form' => $mappingForm->createView(),
            ]
        );
    }

    /**
     * Deletes the dataset
     *
     * @Route("/delete/{id}", name="manage-dataset-delete", requirements={"id" = "\d+"})
     * @Method({"GET"})
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getDoctrine()->getRepository($this->getEntityName())->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('De dataset kon niet gevonden worden.');
        }

        /** @var CsvHandler $handler */
        $handler = $this->get('pdx_csv.handler');
        $handler->removeFile($entity->getCsvName());

        $em->remove($entity);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Dataset verwijderd'
        );

        return $this->redirect($this->generateUrl('manage-dataset-index'));
    }


}
