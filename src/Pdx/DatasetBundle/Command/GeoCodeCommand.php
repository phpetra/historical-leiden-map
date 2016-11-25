<?php

namespace Pdx\DatasetBundle\Command;


use Pdx\Csv\GeoCoder;
use Pdx\Csv\GeoJSONConverter;
use Pdx\DatasetBundle\Entity\DataSet;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeoCodeCommand extends ContainerAwareCommand
{

    private $geoCoder;
    private $em;
    private $logger;

    protected function configure()
    {
        $this
            ->setName('pdx:geocode')
            ->setDescription('Geocode some data')
            ->addArgument('file', InputArgument::REQUIRED, 'Please provide a file')
            ;
    }


    protected function processTheQueue($output)
    {
        $dataSets = $this->getContainer()->get('doctrine')
            ->getRepository('PdxDatasetBundle:DataSet')
            ->findBy(['status' => DataSet::STATUS_QUEUED]
            );

        $this->logger->addInfo('Start geocoding the queue.');

        foreach ($dataSets as $dataSet) {
            $output->writeln('Handling ' . $dataSet->getTitle());
            $this->processOne($dataSet, $output);
        }

    }

    protected function processOne($dataSet, $output)
    {
        $this->logger->addInfo('Started geocoding for file ' . $dataSet->getCsvName());
        $this->geoCoder->setMapping($dataSet->getMapping());

        $dataSet->setStatus(DataSet::STATUS_GEOCODING_STARTED);
        $this->em->persist($dataSet);
        $this->em->flush();

        $this->geoCoder->addGeometriesToCsv($dataSet->getCsvName(), false);

        $dataSet->setStatus(DataSet::STATUS_GEOCODED);
        $this->em->persist($dataSet);
        $this->em->flush();

        $this->logger->addInfo('Finished geocoding for file ' . $dataSet->getCsvName());

        $this->logger->addInfo('Start creating the geoJSON files');

        // also create GeoJSON
        /** @var GeoJSONConverter $jsonCoder */
        $jsonCoder = $this->getContainer()->get('pdx_csv.geojson');

        $jsonCoder->setMapping($dataSet->getMapping());
        $jsonCoder->createGeoJSONFiles($dataSet->getCsvName(), false);

        $dataSet->setStatus(DataSet::STATUS_GEOJSONED);
        $this->em->persist($dataSet);
        $this->em->flush();

        $this->logger->addInfo('Finished creating geoJSON files for ' . $dataSet->getCsvName());

        $message = \Swift_Message::newInstance()
            ->setSubject('CSV-bestand verwerkt')
            ->setFrom(array('bram.zelfde@gmail.com'))
            ->setTo($dataSet->getCreator()->getEmail())
            ->setBody("Beste {$dataSet->getCreator()->getUsername()},

Je dataset {$dataSet->getTitle()} is nu gegeocodeerd.

De data kan nu gebruikt worden om een kaart te maken.

                ");
        $this->getContainer()->get('mailer')->send($message);

        $this->logger->addInfo('Finished geocoding for file ' . $dataSet->getCsvName());
        $output->writeln('All done');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        /** @var GeoCoder $geoCoder */
        $this->geoCoder = $this->getContainer()->get('pdx_csv.geocoder');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->logger = $this->getContainer()->get('logger');


        if ($file === 'all') {
            return $this->processTheQueue($output);
        } else {
            $dataSet = $this->getContainer()->get('doctrine')
                ->getRepository('PdxDatasetBundle:DataSet')
                ->findOneBy(['csvName' => $file]
                );

            return $this->processOne($dataSet, $output);
        }
    }

}
