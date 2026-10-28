<?php

namespace Lucinda\Framework;

/**
 * Detects an XML file on disk and answers with its SimpleXMLElement representation
 * 
 * Absolute file path must be supplied to it, then it will:
 * 1. detect if file exists (if not, a FileNotFoundException is thrown)
 * 2. attempt to read it with SimpleXML (if malformed, an XmlException is thrown)
 */
final class XmlDetector
{
    private \SimpleXMLElement $xml;

    /**
     * Bootstraps XML detection
     * 
     * @param string $xmlPath Absolute path to the XML file (without extension)
     * @throws  XmlException If file not found or malformed
     */
    public function __construct(string $xmlPath)
    {
        $this->setXML($xmlPath);
    }

    /**
     * Checks if file exists on disk and reads it with SimpleXML.
     * 
     * @param string $xmlPath Absolute path to the XML file (without extension)
     * @throws  XmlException If file not found or malformed
     * @return void
     */
    private function setXML(string $xmlPath): void
    {
        $xmlFile = $xmlPath.".xml";
        if (!file_exists($xmlFile)) {
            throw new XmlException(
                "XML file not found: ".$xmlFile
            );
        }
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            throw new XmlException(
                "Templating XML is malformed: ".$xmlFile
            );
        }
        $this->xml = $xml;
    }

    /**
     * Gets SimpleXML representation of tha file in question
     * 
     * @return \SimpleXMLElement
     */
    public function getXML(): \SimpleXMLElement
    {
        return $this->xml;
    }
}