<?php
/**
 * Class nDOMDocument
 * Extension of the DOMDocument class
 * @version     2013-08-28
 * @author		Nicolas Le Thierry d'Ennequin
 */
class nDOMDocument extends DOMDocument {


    /**
     * Constructor
     * @version	2013-08-28  Setting default values for constructor params instead of Null
     * @param		$version		(str) Version XML (eg "1.0")
     * @param		$encoding		(str) Encodage (eg "utf-8")
     */
    public function __construct ($version = "1.0", $encoding = "utf-8") {
        parent::__construct($version, $encoding);
        $this->registerNodeClass("DOMDocument", "nDOMDocument"); // IMPORTANT - cf http://stackoverflow.com/questions/2585879
        $this->registerNodeClass("DOMElement", "nDOMElement"); // IMPORTANT - cf http://stackoverflow.com/questions/2585879
    }
  

    /**
     * selectNodes : selection d'un ensemble de noeuds par une expression XPath
     * @version	2010-04-02
     * @param		$xpath			(str) Expression XPath
     * @return		DOMNodeList
     */
    public function selectNodes ($xpath){
        $oxpath = new DOMXPath($this);
        return $oxpath->query($xpath);
    }


    /**
     * nDOMDocument::selectSingleNode
     * Sélection d'un noeud par une expression XPath
     * Si l'expression XPath selectionne plusieurs noeuds, seul le premier est renvoye.
     * @version	2010-04-02
     * @param		$xpath			(str) Expression XPath
     * @return		nDOMElement
     */
    public function selectSingleNode ($xpath){
        return $this->selectNodes($xpath)->item(0);
    }
  

    /**
     * nDOMDocument::transformNode
     * Applique une transformation XSLT au document et le renvoie sous forme de chaine
     * Le parametre $xslDoc peut etre le chemin relatif du fichier XSLT ou l'objet DOMDocument XLST.
     * @version	2013-09-11 (valeur par défaut de $xslVars)
     * @param		$xslDoc			(String) Chemin relatif du fichier XSLT
     * @param		$xslDoc			(String|DOMDocument) Document DOM XSLT ou son chemin relatif
     * @param		$xslVars		(Array) Liste clés/valeurs a passer en parametre comme variables globales XSLT
     * @return		String
     */
    public function transformNode ($xslDoc, $xslVars = array()) {

        if (is_string($xslDoc)) {
            $xsl = $xslDoc;
            $xslDoc = new nDOMDocument;
            $xslDoc->load($xsl);
        }

        if (is_array($xslVars)) {
            foreach($xslVars as $key => $value){
                $xslDoc->selectSingleNode("/xsl:stylesheet/xsl:variable[@name='".$key."']")->nodeValue = $value;
            }
        }

        $oxslt = new XSLTProcessor();
        $oxslt->importStylesheet($xslDoc);
        return $oxslt->transformToXML($this);
    }
    

    /**
     * nDOMDocument::transformNodeToDoc
     * Applique une transformation XSLT au document et le renvoie sous forme d'objet nDOMDocument
     * Le parametre $xslDoc peut etre le chemin relatif du fichier XSLT ou l'objet DOMDocument XLST.
     * @version     2012-09-24
     * @param		$xslDoc			(String) Chemin relatif du fichier XSLT
     * @param		$xslDoc			(nDOMDocument) Document DOM XSLT
     * @param		$xslVars		(Array) Liste cles/valeurs a passer en parametre comme variables globales XSLT
     * @return		objet nDOMDocument
     */
    public function transformNodeToDoc ($xslDoc, $xslVars) {
    
        $xml = $this->transformNode($xslDoc, $xslVars); // Applique la transformation sous forme de chaîne
        $xmlDoc = new nDOMDocument();
        $header = "<?xml version=\"".$this->version."\" encoding=\"".$this->encoding."\"?>";

        $xmlDoc->loadXML($header.$xml);

        return $xmlDoc;
    }

    
    
    /* 2013-09-11 TODO: les deux méthodes ci-dessous getNodeFromTransform et includeDocument
     * pourraient être regroupées dans une méthode d'inclusion générale permettant, à partir d'un document source $xmlDoc :
     * - Facultativement, de lui appliquer une transformation XSLT en un autre document DOM (paramètre : document DOM du XSLT ou chemin du fichier) (par défaut : pas de transformation)
     * - Puis, facultativement, de sélectionner le noeud à inclure (par défaut : documentElement)
     * - De sélectionner facultativement dans le document cible ($this) le noeud dans lequel se fera l'inclusion (par défaut : pas d'inclusion mais simple import vers $this)

    /**
     * nDOMDocument::getNodeFromTransform
     * Renvoie le noeud résultant d'une transformation XSLT, en le rattachant à nDOMDocument
     * NB : le noeud est rattaché à nDOMDocument mais n'est pas placé dans l'arbre
     * @version     2012-09-26
     * @param       $xmlDoc         (nDOMDocument) Document DOM sur lequel appliquer une transformation
     * @param       $xslDoc         (nDOMDocument) Document DOM XSLT
     * @param       $xslVars        (Array) Liste cles/valeurs a passer en parametre comme variables globales XSLT 
     * @return      objet nDOMNode
     * TODO : faut-il contrôler que la transformation renvoie bien du XML ? 
     */
    public function getNodeFromTransform ($xmlDoc, $xslDoc, $xslVars) {
        $xmlDoc2 = $xmlDoc->transformNodeToDoc($xslDoc, $xslVars);
        return $this->importNode($xmlDoc2->documentElement, true);
    }

    /**
     * nDOMDocument::include
     * Méthode simple pour inclure un DOMDocument source dans le documentElement du DOMDocument courant
     * @version     2013-09-11
     * @param       $xmlDoc     (nDOMDocument) Document DOM (source) à inclure
     * @return
     */
    public function includeDocument ($xmlDoc) {
        $node = $xmlDoc->selectSingleNode("/*");
        $this->documentElement->appendChild($this->importNode($node, true));
        return true;
    }
}



/**
 * Class nDOMElement
 * Extension of the DOMElement class
 * @version     2010-04-10
 * @author      Nicolas Le Thierry d'Ennequin
 */
class nDOMElement extends DOMElement {

    /**
     * selectNodes : selection d'un ensemble de noeuds par une expression XPath
     * @version     2010-04-02
     * @param		$xpath			(str) Expression XPath
     * @return		DOMNodeList
     */
    public function selectNodes ($xpath) {
        $oxpath = new DOMXPath($this->ownerDocument);
        return $oxpath->query($xpath, $this);
    }


    /**
     * selectSingleNode : selection d'un noeud par une expression XPath
     * Si l'expression XPath selectionne plusieurs noeuds, seul le premier est renvoye.
     * @version	2010-04-02
     * @param		$xpath			(str) Expression XPath
     * @return		nDOMElement
     */
    public function selectSingleNode($xpath) {
        return $this->selectNodes($xpath)->item(0);
    }
}
