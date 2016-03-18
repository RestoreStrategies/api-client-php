<?php

// TODO: Add POST template creation
// TODO: Refactor

class SignUp {
    protected $rawCollection = null;
    protected $template;
    protected $html = [];
    protected $idHeader = "ftc-signup";
    protected $validationTypes = array("inclusion", "exclusion", "presence", "length", "multi", "range", "fileSize", "fileType");
    protected $domDoc;

    public function __construct($jsonobject) {
        $this->rawCollection = json_encode($jsonobject);
        $this->template = $this->_parseTemplate($jsonobject->collection->template);
    }

    /**
     * Creates the appropriate html form elements for each data field.
     * @param DOMDocument $domDocument      An instance of DOMDocument which the html fields will be attached to.
     * @param string $dataName              The name of an object in the signup data array.
     * @param string $class                 A string which will be appended to the class of each html field.
     * @param primitive $dependentOption    An option used to construct the appropriate html if the data has
     * 							            some dependencies on other data values.
     * @return DOMDocumentFragment          A DOMDocumentFragment containing the created html fields.
     */
    public function getHTML($domDocument, $dataName, $class, $dependentOption = null) {
        $this->domDoc = $domDocument;
        $fragment = $this->domDoc->createDocumentFragment();

        if (!is_string($dataName) ||
            !is_string($class)) {

            return null;
        }

        $element = null;
        $class = $class . " " . $this->idHeader;
        $data = $this->template[$dataName];

        if ($data !== null) {
            $prompt = $data->prompt;

            if ($dataName === "givenName") {
                $element = $this->_createHTMLInput($class, $dataName, "text", "given-name", null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "familyName") {
                $element = $this->_createHTMLInput($class, $dataName, "text", "family-name", null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "telephone") {
                $element = $this->_createHTMLInput($class, $dataName, "tel", "tel", null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "email") {
                $element = $this->_createHTMLInput($class, $dataName, "email", "email", null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "hasChurch") {
                $element = $this->_createSelectionList($class, $dataName, $dependentOption, "radio", $prompt);
            }
            else if($dataName === "church") {
                $element = $this->_createSelectionList($class, $dataName, $dependentOption, "radio", $prompt);
            }
            else if($dataName === "churchOther") {
                $element = $this->_createHTMLInput($class, $dataName, "text", null, null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "churchCampus") {
                $element = $this->_createSelectionList($class, $dataName, $dependentOption, "radio", $prompt);
            }
            else if($dataName === "comment") {
                $element = $this->_createHTMLInput($class, $dataName, "textarea", null, null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "numOfItemsCommitted") {
                $element = $this->_createHTMLInput($class, $dataName, "number", null, null);
                $this->_addValidations($dataName, $dependentOption, $element);
                $element = $this->_wrapHTMLElementInLabel($element, $prompt, $class);
            }
            else if($dataName === "lead") {
                $element = $this->_createSelectionList($class, $dataName, $dependentOption, "checkbox", $prompt);
            }
        }

        if ($element !== null) {
            $fragment->appendChild($element);
        }

        return $fragment;
    }

    /**
     * Returns the raw signup data as a string.
     * @return string   The raw signup data.
     */
    public function getRawSignupData() {
        return $this->rawCollection;
    }

    /**
     * Returns the signup data as an object.
     * @return object The signup data object.
     */
    public function getSignupData() {
        return $this->template;
    }

    /**
     * Returns the optional values a data field can have.
     * @param  string $dataName             The name of an object in the signup data array.
     * @param  primitive $dependencyValue   An option used to find the appropriate values.
     * @return array                        An array containing the optional values.
     */
    public function getOptions($dataName, $dependencyValue = null) {
        $options = [];
        $data = $this->getActiveValidations($dataName, $dependencyValue);
        if ($data !== null) {
            $inclusions = $data["inclusion"];
            if ($inclusions !== null) {
                $inclusion = $inclusions[0];
                if ($inclusion !== null) {
                    $args = $inclusion->arguments;
                    if ($args !== null) {
                        foreach ($args as $arg) {
                            if ($arg->value !== null) {
                                $options[] = $arg->value;
                            }
                        }
                    }
                }
            }
        }
        return $options;
    }

    public function getOpportunityID() {
        return $this->oppID;
    }

    public function getValidations($name) {
        $validations = [];

        if ($this->template[$name] !== null) {
            $data = $this->template[$name];

            if ($data !== null) {
                $validations = $data->validations;
            }
        }

        return $validations;
    }

    public function getValidation($dataName, $validationType) {
        $validations = $this->getValidations($dataName);
        $typeValidations = $validations[$validationType];
        return $typeValidations;
    }

    public function getActiveValidations($dataName, $value) {
        $validationsAll = array();

        foreach ($this->validationTypes as $validationType) {
            $validations = $this->getActiveValidation($dataName, $validationType, $value);
            $validationsAll[$validationType] = $validations;
        }

        return $validationsAll;
    }

    public function getActiveValidation($dataName, $validationType, $value) {
        $activeValidations = [];
        $validations = $this->getValidation($dataName, $validationType);
        if ($validations !== null) {
            foreach ($validations as $validation) {
                $dependencies = $validation->dependencies;
                $isActive = $this->_isActiveValidation($validation, $value);

                if ($isActive) {
                    $activeValidations[] = $validation;
                }
            }
        }

        return $activeValidations;
    }

    private function _createSelectionList ($class, $name, $value, $type, $legendText) {
        $fieldSet = null;
        $class = $class;

        if ($type === "checkbox") {
            $inputClass = $inputClass . " checkbox-group";
        }

        $validations = $this->getActiveValidations($name, $value); // Change to get active validations
        $inclusions = $validations["inclusion"]; // Get the appropriate data to create the list
        $inclusion = $inclusions[0];
        $presences = $validations["presence"];
        $required = $presences[0];

        if ($inclusion !== null) {
            if ($inclusion->arguments !== null) {
                $fieldSet = $this->_createHTMLFieldSet($class, $dataName, $legendText);
                foreach ($inclusion->arguments as $arg) {
                    if ($arg->name === "option") {
                        $input = $this->_createHTMLInput($inputClass, $name, $type, null, $arg->value);

                        if ($type === "radio" && $required !== null) {
                            $input->setAttribute("required", "");
                        }

                        $label = $this->_createHTMLElementLabel($input, $arg->prompt, $class . " " . $type);
                        $span = $this->domDoc->createElement("span");
                        $span->appendChild($input);
                        $span->appendChild($label);
                        $fieldSet->appendChild($span);
                    }
                }
            }
        }
        return $fieldSet;
    }

    private function _isActiveValidation ($validation, $value) {

        // If the value is a valid dependency value or no dependencies exist, return true
        $isActive = false;
        $dependencies = $validation->dependencies;

        if (count($dependencies) === 0) {
            $isActive = true;
        }
        else {

            foreach ($dependencies as $dependency) {

                if ($dependency->value === $value) {
                    $isActive = true;
                    break;
                }
            }
        }

        return $isActive;
    }

    private function _createHTMLFieldSet($class, $name, $legendText) {
        $class = $class . " fieldset";
        $id = $this->idHeader . "-" . $name;
        $element = $this->domDoc->createElement("fieldset");
        $this->_setHTMLElementClassAndID($element, $class, $id);
        $legend = $this->domDoc->createElement("legend", $legendText);
        $element->appendChild($legend);
        return $element;
    }

    private function _createHTMLInput($class, $name, $type, $autoComplete, $val) {
        // Generate appropriate class and ID
        $class = $class . " " . $type;
        $id = $this->idHeader . "-" . $name;

        if (is_string($val) && strlen($val) > 0) {
            $id = $id . "-" . $val;
        }

        $input = null;

        if ($type === "textarea") {
            $input = $this->domDoc->createElement("textarea");
        }
        else {
            $input = $this->domDoc->createElement("input");
            $input->setAttribute("type", $type);
        }

        $this->_setHTMLElementClassAndID($input, $class, $id);
        $input->setAttribute("name", $name);

        if (is_string($autoComplete)) {
            $input->setAttribute("autocomplete", $autoComplete);
        }

        if (is_string($val)) {
            $input->setAttribute("value", $val);
        }

        return $input;
    }

    private function _wrapHTMLElementInLabel($element, $val, $class) {
        // Generate appropriate class and ID
        $class = $class . " label";

        $id = $element->getAttribute("id");

        if (is_string($id) && strlen($id) > 0) {
            $id = $id . "-label";
        }

        $label = $this->domDoc->createElement("label", $val);
        $this->_setHTMLElementClassAndID($label, $class, $id);
        $label->appendChild($element);
        return $label;
    }

    private function _createHTMLElementLabel($element, $val, $class) {

        $id = $element->getAttribute("id");

        if (is_string($id) && strlen($id) > 0) {
            $id = $id . "-label";
        }

        $label = $this->domDoc->createElement("label", $val);
        $this->_setHTMLElementClassAndID($label, $class, $id);
        $label->setAttribute("for", $element->getAttribute("id"));
        return $label;
    }

    private function _setHTMLElementClassAndID($element, $class, $id) {
        $element->setAttribute("class", $class);
        $element->setAttribute("id", $id);
    }

    public function _parseTemplate($template) {
        $dataArray = $template->data;
        $newTemplate = [];

        foreach ($dataArray as $data) {
            $name = $data->name;
            $validations = $data->validations;
            $newValidations = [];
            if ($validations !== null) {
                foreach ($validations as $validation) {
                    $vName = $validation->name;
                    if ($newValidations[$vName] === null) {
                        $newValidations[$vName] = [];
                    }
                    $newValidations[$vName][] = $validation;
                }

                $data->validations = $newValidations;
            }
            $newTemplate[$name] = $data;
        }

        return $newTemplate;
    }

    private function _addValidations($name, $val, $element) {
        $validations = $this->getActiveValidations($name, $val);

        $multi = false;
        $optionType = null;

        foreach ($validations as $validationType=>$validationsByType) {

            if ($validationType === "length") {

                foreach ($validationsByType as $validation) {
                    // add maxlength and minlength appropriately
                    if ($validation->arguments !== null) {
                        foreach ($validation->arguments as $argKey=>$argument) {
                            $argName = $argument->name;
                            $argVal = $argument->value;

                            if ($argVal === null) {
                                continue;
                            }

                            if ($argName === "min" ||
                                $argName === "max") {

                                $element->setAttribute($argName, $argVal);
                            }
                        }
                    }
                }
            }

            if ($validationType === "range") {

                foreach ($validationsByType as $validation) {
                    if ($validation->arguments !== null) {
                        foreach ($validation->arguments as $argument) {
                            $argName = $argument->name;
                            $argVal = $argument->value;

                            if ($argVal === null) {
                                continue;
                            }

                            if ($argName === "min" ||
                                $argName === "max" ||
                                $argName === "step") {

                                $element->setAttribute($argName, $argVal);
                            }
                        }
                    }
                }
            }

            foreach ($validationsByType as $validation) {
                if ($validationType === "presence") {
                    $element->setAttribute("required","");
                }
            }
        }
    }
}
