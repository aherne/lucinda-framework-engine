<?php
/**
 * Locates, loads and validates class on disk
 */
class ClassLoader
{
    /**
     * Performs detection process
     *
     * @param string $classFolder Relative location of folder class lies into.
     * @param string $className Name of class (including namespace, if applies)
     * @throws ServletException If validation fails (file or class not found)
     */
    public function __construct($classFolder, $className) {
        $this->load($classFolder, $className);
    }

    /**
     * Performs detection process
     *
     * @param string $classFolder Relative location of folder class lies into.
     * @param string $className Name of class (including namespace, if applies)
     * @throws ServletException If validation fails (file or class not found)
     */
    private function load($classFolder, $className) {
        // validate if class isn't empty
        if(!$className) throw new ServletException("Class is empty!");

        // get actual class name without namespace
        $slashPosition = strpos($className, "\\");
        $simpleClassName = ($slashPosition!==false?substr($className,$slashPosition+1):$className);

        // loads class file
        $filePath = $classFolder."/".$simpleClassName.".php";
        if(!file_exists($filePath)) throw new ServletException("File not found: ".$simpleClassName);
        require_once($filePath);

        // validates if class exists
        if(!class_exists($className)) throw new ServletException("Class not found: ".$className);
    }
}