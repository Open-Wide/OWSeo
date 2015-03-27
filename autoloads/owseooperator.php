<?php 

class OWSeoOperator {
	
    var $Operators;

    function __construct() {
        $this->Operators = array_keys($this->namedParameterList());
    }

    function &operatorList() {
        return $this->Operators;
    }

    function namedParameterPerOperator() {
        return true;
    }
	
    function namedParameterList() {
        return array (
            'seo_parse' => array(
                'class_identifier' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => null,
                ),
                'node_id' => array(
                    'type' => 'int',
                    'required' => false,
                    'default' => null,
                ),
                ),
                'seo_metadata' => array(
                    'node_id' => array(
                    'type' => 'int',
                    'required' => true,
                    'default' => null,
                ),
            ),
        );
    }
	
    function modify ( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters ) {
		
        switch ($operatorName) {
            case 'seo_metadata' :
                $node_id = $namedParameters['node_id'];
                $operatorValue = $this->seoMetaData($node_id);
                break;

            case 'seo_parse' :
                $classIdentifier = $namedParameters['class_identifier'];
                $nodeId = isset($namedParameters['node_id']) ? $namedParameters['node_id'] : '';

                if ($nodeId) {
                    $node = eZContentObjectTreeNode::fetch( $nodeId );
                } else {
                    $node = null;
                }
                $operatorValue = $this->seoParse($classIdentifier, $node);
                break;
        }
	  }
	
    /*********************************************************************
    OPERATORS
    *********************************************************************/

    function seoMetaData( $nodeId ) {

        $node = eZContentObjectTreeNode::fetch( $nodeId );

        if ( $node instanceof eZContentObjectTreeNode ) {
            return $this->seoParse($node->ClassIdentifier, $node);
        }
        return false;
    }
	
    /*
    * With parameter "node" => return data of the node with variables replaced (view mode)
    * Without parameter "node" => return data mask with variables like '{{title}}' (edit mode)
    */
    function seoParse($classIdentifier, $node = null) {

        $iniSeo = eZIni::instance('owseo.ini');
        $seoTypes = array('title', 'keywords', 'description');
        $nodeValues = array();
        $rules = array();
        $seoValues = array();
        $dataMap = array();
        $availableVariables = array();
        $charVariableStart = $iniSeo->variable('GeneralSettings', 'CharVariableStart');
        $charVariableEnd = $iniSeo->variable('GeneralSettings', 'CharVariableEnd');
        $maxLengthSettings = $iniSeo->group('MaxLengthSettings');
        $defaultRulesSettings = $iniSeo->group('DefaultRules');
        $seoSettings = array();
        $availableSettings = array('PathVarName', 'PathStringMinDepth', 'PathStringMaxLevels', 'PathStringSeparator');
        $path = array();

        // General settings for a given class
        foreach ($availableSettings as $settingsType) {
            $seoSettings[$settingsType] = $iniSeo->variable('GeneralSettings', $settingsType);
        }

        // Specific settings for a given class
        foreach ($availableSettings as $settingsType) {
            if ($iniSeo->hasVariable('Rules_' . $classIdentifier, $settingsType)) {
                $seoSettings[$settingsType] = $iniSeo->variable('Rules_' . $classIdentifier, $settingsType);
            }
        }
        $seoSettings['PathStringSeparator'] = str_replace('"', '', $seoSettings['PathStringSeparator']);

        // General seo rules for a given class
        foreach ($seoTypes as $seoType) {
            if ($iniSeo->hasVariable('Rules_' . $classIdentifier, $seoType)) {
                $rules[ $seoType ] = $iniSeo->variable('Rules_' . $classIdentifier, $seoType);
            }
        }

        // Is there overrided data in the content ?
        if ( isset($node) && $node instanceof eZContentObjectTreeNode ) {
            $dataMap = $node->dataMap();

            // Find saved data from the node
            foreach ( $dataMap as $attribute ) {
                if ( $attribute->DataTypeString == OWSeoType::DATA_TYPE_STRING ) {
                    // SEO Datatype found
                    $content = $attribute->content();
                    foreach ($seoTypes as $seoType) {
                        $nodeValues[ $seoType ] = $content->{$seoType};
                    }
                }
            }

            // Construct pathString
            $pathArray = $node->pathArray();
            $nbLevels = 0;
            for ($depth = $node->Depth-1; $depth >= $seoSettings['PathStringMinDepth']; $depth--) {
                $pathNode = eZContentObjectTreeNode::fetch( $pathArray[$depth] );
                $path[] = $pathNode->Name;

                $nbLevels++;
                if ($nbLevels > $seoSettings['PathStringMaxLevels']) {
                    break;
                }
            }
        }

        if ($node) {
            $availableVariables['ContentName'] = $node->Name;
        }

        // Available variables from the content or from general settings
        foreach ($iniSeo->group('GeneralVariables') as $k => $v) {
            $availableVariables[$k] = $v;
        }
        $availableVariables[$seoSettings['PathVarName']] = implode($seoSettings['PathStringSeparator'], $path);
        foreach ( array_keys($dataMap) as $attributeName ) {
            $availableVariables[$attributeName] = $dataMap[ $attributeName ]->content();
        }

        foreach ( $seoTypes as $seoType) {
            if ( isset($nodeValues[ $seoType ]) && $nodeValues[ $seoType ] ) {
                // Overrided data found in the content
                $seoValues[ $seoType ] = $nodeValues[ $seoType ];
            } elseif (isset($rules[ $seoType ]) || isset($defaultRulesSettings[ $seoType ]) ) {
                if (isset($rules[ $seoType ])) {
                    // Overrided rules for a given class
                    $seoString = $rules[ $seoType ];
                } else {
                    // Default rule;
                    $seoString = $defaultRulesSettings[ $seoType ];
                }

                // Extract variables from string
                $regex = "/" . $charVariableStart . "([^" . $charVariableStart . "]+)" . $charVariableEnd . "/";
                preg_match_all($regex, $seoString, $matches);
                if (isset($matches[1]) && count($matches[1])) {
                    foreach ($matches[1] as $variable) {
                        $exploded = explode('|', $variable);
                        foreach ($exploded as $explodedVariable) {
                            if (array_key_exists($explodedVariable, $availableVariables) && $availableVariables[$explodedVariable] != '') {
                                $variableReplaced = $availableVariables[$explodedVariable];
                                $seoString = str_replace($charVariableStart . $variable . $charVariableEnd, $variableReplaced, $seoString);
                                ezDebug::writeDebug("OwSeo : Replace $variable by $variableReplaced");
                                break;
                            }
                        }
                    }
                }

                $seoValues[ $seoType ] = $seoString;
            } else {
                // No overrided data => default value
                $seoValues[ $seoType ] = '';
            }
            $seoValues[ $seoType ] = substr($seoValues[ $seoType ], 0, $maxLengthSettings[ $seoType ]);
        }

        return $seoValues;
    }
}
