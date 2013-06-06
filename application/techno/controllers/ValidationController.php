<?php
/**
 * @author matthieu.napoli
 * @package Techno
 */

use Core\Annotation\Secure;

/**
 * @package Techno
 */
class Techno_ValidationController extends Core_Controller
{

    /**
     * @Secure("editTechno")
     */
    public function resultsAction()
    {
        /** @var $validationService Techno_Service_Validator */
        $validationService = Techno_Service_Validator::getInstance();

        $this->view->keywordMeaningsErrors = $validationService->validateMeaningsKeywords();
        $this->view->keywordFamilyTagsErrors = $validationService->validateFamilyTagsKeywords();
        $this->view->keywordFamilyMembersErrors = $validationService->validateFamilyMembersKeywords();
    }

}
