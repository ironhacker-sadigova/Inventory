<?php
/**
 * @author valentin.claras
 * @author cyril.perraud
 * @package    Orga
 * @subpackage Controller
 */

use Core\Annotation\Secure;

/**
 * @package    Orga
 * @subpackage Controller
 */
class Orga_Datagrid_Cell_ReportController extends UI_Controller_Datagrid
{
    /**
     * Fonction renvoyant la liste des éléments peuplant la Datagrid.
     * @Secure("viewReport")
     */
    public function getelementsAction()
    {
        $this->request->aclFilter->enabled = true;
        $this->request->aclFilter->user = $this->_helper->auth();
        $this->request->aclFilter->action = User_Model_Action_Default::VIEW();

        $this->request->filter->addCondition(DW_Model_Report::QUERY_PROJECT, $this->getParam('idProject'));
        $this->request->order->addOrder(DW_Model_Report::QUERY_LABEL);
        foreach (DW_Model_Report::loadList($this->request) as $report) {
            $data = array();
            $data['index'] = $report->getKey()['id'];
            $data['label'] = $report->getLabel();
            $urlDetails = 'orga/tab_celldetails/report?idCell='.$this->getParam('idCell').'&idReport='.$data['index'];
            $data['details'] = $this->cellLink($urlDetails, __('UI', 'name', 'details'), 'share-alt');

            /** @var User_Service_ACL $aclService */
            $aclService = $this->get('User_Service_ACL');
            $isUserAllowedToDeleteReport = $aclService->isAllowed(
                $this->_helper->auth(),
                User_Model_Action_Default::DELETE(),
                $report
            );
            if (!$isUserAllowedToDeleteReport) {
                $data['delete'] = false;
            }

            $this->addline($data);
        }
        $this->send(true);
    }

    /**
     * Fonction supprimant un élément.
     * @Secure("deleteReport")
     */
    public function deleteelementAction()
    {
        DW_Model_Report::load(array('id' => $this->delete))->delete();
        $this->message = __('UI', 'messages', 'deleted');
        $this->send();
    }

}