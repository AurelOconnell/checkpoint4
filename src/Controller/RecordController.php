<?php

namespace App\Controller;

use App\Entity\Record;
use App\Form\RecordType;
use App\Repository\RecordRepository;
use App\Service\FileUploader;
use App\Service\ImageVerification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/record')]
class RecordController extends AbstractController
{
    #[Route('/', name: 'app_record_index', methods: ['GET'])]
    public function index(RecordRepository $recordRepository): Response
    {
        return $this->render('record/index.html.twig', [
            'records' => $recordRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_record_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        RecordRepository $recordRepository,
        ImageVerification $imageVerification,
        FileUploader $fileUploader
    ): Response {
        $record = new Record();
        $form = $this->createForm(RecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form->get('recordCover')->getData();

            if (!empty($pictureFile)) {
                if (!$imageVerification->imageVerification($pictureFile)) {
                    $this->addFlash('danger', 'Veuillez utiliser une image au format PNG, JPG ou JPEG');
                } else {
                    $pictureFilename = $fileUploader->upload($pictureFile);
                    $record->setRecordCover($pictureFilename);
                }
            }

            $recordRepository->save($record, true);

            return $this->redirectToRoute(
                'app_artist_show',
                ['id' => $record->getArtist()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('record/new.html.twig', [
            'record' => $record,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_record_show', methods: ['GET'])]
    public function show(Record $record): Response
    {
        return $this->render('record/show.html.twig', [
            'record' => $record,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_record_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Record $record, RecordRepository $recordRepository): Response
    {
        $form = $this->createForm(RecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recordRepository->save($record, true);

            return $this->redirectToRoute('app_record_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('record/edit.html.twig', [
            'record' => $record,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_record_delete', methods: ['POST'])]
    public function delete(Request $request, Record $record, RecordRepository $recordRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $record->getId(), $request->request->get('_token'))) {
            $recordRepository->remove($record, true);
        }

        return $this->redirectToRoute('app_record_index', [], Response::HTTP_SEE_OTHER);
    }
}
