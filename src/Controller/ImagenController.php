<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Imagen;
use App\Form\ImagenType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;


class ImagenController extends AbstractController
{
    /**
     * @Route("/registrar-imagen", name="RegistrarImagen")
     */
    public function index(Request $request, SluggerInterface $slugger)
    {
        $imagen = new Imagen();
        $form = $this->createForm(ImagenType::class, $imagen);
        $form->handleRequest($request);
        if($form->isSubmitted()&&$form->isValid()){
            $user = $this->getUser();
            $imagen->setUser($user);
            $brochureFile = $form->get('foto')->getData();
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Algo ha salido mal');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $imagen->setFoto($newFilename);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($imagen);
            $em->flush();
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('imagen/index.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/imagen/{id}", name="VerImagen")
     */
    public function VerImagen($id){
        $em = $this->getDoctrine()->getManager();
        $imagen = $em->getRepository(Imagen::class)->find($id);
        return $this->render('imagen/verImagen.html.twig', [
            'imagen' => $imagen
        ]);

    }

    /**
     * @Route("/mis-imagenes", name="MisImagenes")
     */
    public function MisImagenes(){
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $imagen = $em->getRepository(Imagen::class)->findBy(['user'=>$user]);
        return $this->render('imagen/misImagenes.html.twig', [
            'imagen' => $imagen
        ]);
    }
}
