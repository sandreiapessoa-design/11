<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Affiliate.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

# models
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Affiliate\Suppression as Suppression;

/**
 * @name Affiliate
 * @description Affiliate WebService
 */
class Affiliate extends Base
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
    /**
     * @name init
     * @description initializing process before the action method executed
     * @once
     * @protected
     */
    public function init() 
    {
        # set the current application to a local variable
        $this->app = Application::getCurrent();
    }
    
    /**
     * @name getOffers
     * @description get affiliate network offers action
     * @before init
     */
    public function getOffers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Offers','suppression');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $affiliateNetworkIds = $this->app->utils->arrays->get($parameters,'affiliate-network-ids',[]);

        if(is_array($affiliateNetworkIds) && count($affiliateNetworkIds) > 0)
        {
            $offers = Offer::all(Offer::FETCH_ARRAY,['affiliate_network_id IN ?',[$affiliateNetworkIds]],['id','name','production_id','affiliate_network_name']);

            if(count($offers) == 0)
            {
                Page::printApiResults(500,'Offers not found !');
            }

            Page::printApiResults(200,'',['offers' => $offers]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect affiliate network id !');
        }
    }

    /**
     * @name getOffersDetails
     * @description get affiliate network offers details action
     * @before init
     */
    public function getOffersDetails($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Offers','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $offerIds = $this->app->utils->arrays->get($parameters,'offers-ids',[]);

        if(is_array($offerIds) && count($offerIds) > 0)
        {
            $offers = Offer::all(Offer::FETCH_ARRAY,['id IN ?',[$offerIds]],['id','name','description','rules']);
            
            if(count($offers) == 0)
            {
                Page::printApiResults(500,'No offers found !');
            }
            
            $table = "<table class='table table-bordered table-striped table-condensed'>";
            $table .= "<thead><tr>";
            $table .= "<th>Id</th><th style='width: 15%;'>Name</th><th style='width: 35%;'>Description</th><th>Rules</th>";
            $table .= "</tr></thead>";
            $table .= "<tbody>";
            
            foreach ($offers as $offer)
            {
                $table .= "<tr>";
                $table .= "<td>{$offer['id']}</td>";
                $table .= "<td>{$offer['name']}</td>";
                $table .= "<td>" . str_replace(["\r\n","\r","\n"],'<br/>',base64_decode($offer['description'])) . "</td>";
                $table .= "<td>" . str_replace(["\r\n","\r","\n"],'<br/>',base64_decode($offer['rules'])) . "</td>";
                $table .= "</tr>";
                
            }
            
            $table .= "</tbody></table>";
            
            Page::printApiResults(200,'',['details' => $table]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect offers ids !');
        }
    }
    
    /**
     * @name startSuppression
     * @description start suppression action
     * @before init
     */
    public function startSuppression($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Offers','suppression');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $offerIds = $this->app->utils->arrays->get($parameters,'offers',[]);

        if(!is_array($offerIds) || count($offerIds) == 0)
        {
            Page::printApiResults(500,'Please provide at least one offer !');
        }
        
        $listsIds = $this->app->utils->arrays->get($parameters,'lists',[]);

        if(!is_array($listsIds) || count($listsIds) == 0)
        {
            Page::printApiResults(500,'Please provide at least one list !');
        }

        foreach ($offerIds as $offerId)
        {
            $supp = Suppression::first(Suppression::FETCH_ARRAY,['offer_id = ? AND status = ?',[$offerId,'In Progress']],['id']);
            
            if(count($supp) == 0)
            {
                $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',[$offerId]],['affiliate_network_id','affiliate_network_name','name']);
                
                if(count($offer))
                {
                    # create a process object
                    $process = new Suppression();
                    $process->setStatus('In Progress');
                    $process->setAffiliateNetworkId($offer['affiliate_network_id']);
                    $process->setAffiliateNetworkName($offer['affiliate_network_name']);
                    $process->setOfferId($offerId);
                    $process->setOfferName($offer['name']);
                    $process->setListsIds(implode(',',$listsIds));
                    $process->setProgress('0%');
                    $process->setEmailsFound('0');
                    $process->setStartTime(date('Y-m-d H:i:s'));    
                    $process->setFinishTime(null);    

                    # call iresponse api
                    Api::call('Affiliate','startSuppression',['process-id' => $process->insert()],true);
                }
            }
        }
        
        Page::printApiResults(200,'Suppression process(es) started');
    }
    
    /**
     * @name getSupressionDetails
     * @description get suppression details action
     * @before init
     */
    public function getSupressionDetails($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Offers','suppression');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesId = $this->app->utils->arrays->get($parameters,'processes-id',0);

        if($processesId > 0)
        {
            $process = Suppression::first(Suppression::FETCH_ARRAY,['id = ?',$processesId],['id','details']);
            
            if(count($process) == 0)
            {
                Page::printApiResults(500,'No processes found !');
            }
            
            if($process['details'] == null || $process['details'] == '')
            {
                Page::printApiResults(500,'No details found !');
            }
            
            $json = json_decode($process['details'],true);
            
            $table = "<table class='table table-bordered table-striped table-condensed'>";
            $table .= "<thead><tr>";
            $table .= "<th>Affiliate Network</th><th>Offer</th><th>Status</th><th>Emails Found</th>";
            $table .= "</tr></thead>";
            $table .= "<tbody>";
            
            foreach ($json as $row)
            {
                $table .= "<tr>";
                $table .= "<td>{$row['affiliate-netwrok']}</td>";
                $table .= "<td>{$row['offer-name']}</td>";
                $table .= "<td>{$row['suppression-status']}</td>";
                $table .= "<td>{$row['emails-found']}</td>";
                $table .= "</tr>";
            }
            
            $table .= "</tbody></table>";
            
            Page::printApiResults(200,'',['details' => $table]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect processes ids !');
        }
    }
    
    /**
     * @name stopSupressionProcesses
     * @description stop suppression processes action
     * @before init
     */
    public function stopSupressionProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Offers','suppression');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);

        if(!is_array($processesIds) || count($processesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call iresponse api
        $result = Api::call('Affiliate','stopSupressionProcesses',['processes-ids' => $processesIds]);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }
            
        Page::printApiResults(200,$result['message']);
    }
}