<?php

namespace jalder\Upnp;

class Mediaserver extends Core
{

    public function discover()
    {
        return parent::search('urn:schemas-upnp-org:device:MediaServer:1');
    }

    //move to child class
    public function browse($location,$base = '0', $browseflag = 'BrowseDirectChildren')
	{
		libxml_use_internal_errors(true);
		require_once(APPPATH.'modules/upnp/vendor/phpupnp.class.php');
		$upnp = new \phpUPnP();
		$args = array(
			'ObjectID'=>$base,
			'BrowseFlag'=>$browseflag,
			'Filter'=>'',
			'StartingIndex'=>0,
			'RequestedCount'=>0,
			'SortCriteria'=>''
		);
		$response = $upnp->sendRequestToDevice('Browse',$args,$location,$type = 'ContentDirectory');
		if(isset($response['body']['h1']))
		{
			//echo 'Location: '.$location.' Says: '.$response['body']['h1'];
			return false;
		}
		$returned = $response['s:Body']['u:BrowseResponse']['NumberReturned'];
		$total = $response['s:Body']['u:BrowseResponse']['TotalMatches'];
		$data = \Format::forge($response['s:Body']['u:BrowseResponse']['Result'],'xml:ns')->to_array();
		return $data;
	}

    //move to Core    
	public function getControlURL($description_url)
	{
		$baseurl = $this->getBaseURL($description_url);
		$description = $this->parseDescription($description_url);
		foreach($description['device']['serviceList']['service'] as $s)
		{
			if($s['serviceId'] == 'urn:upnp-org:serviceId:ContentDirectory')
			{
				return $baseurl.$s['controlURL'];
			}
		}
		return $description;
	}

}
