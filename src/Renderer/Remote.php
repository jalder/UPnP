<?php
/** AVTransport UPnP Class
 * Used for controlling renderers
 *
 * @author jalder
 */

namespace jalder\Upnp\Renderer;

use jalder\Upnp;

class Remote
{

	/**
	 * Renderers may have the following UPnP Methods:
	 * SetAVTransportURI
	 * SetNextAVTransportURI
	 * GetMediaInfo
	 * GetMediaInfo_Ext
	 * GetTransportInfo
	 * GetPositionInfo
	 * GetDeviceCapabilities
	 * GetTransportSettings
	 * Stop
	 * Play
	 * Pause
	 * Record
	 * Seek
	 * Next
	 * Previous
	 * Ext_Exit
	 * SetPlayMode
	 * SetRecordQualityMode
	 * GetCurrentTransportActions
	 */

	public $ctrlurl;
	private $upnp;

	public function __construct($device)
	{
        $this->upnp = new Upnp\Core();
        $this->ctrlurl = $device;
        if(is_array($device['description']['device']['serviceList']['service'])){
            foreach($device['description']['device']['serviceList']['service'] as $service){
                if($service['serviceId'] == 'urn:upnp-org:serviceId:AVTransport'){
                    $this->ctrlurl = $this->upnp->baseUrl($device['location']).$service['controlURL'];
                }
            }
        }

	}

	public function play($url = "")
    {
        if($url === ""){
            return self::unpause();
        }
		$args = array(
			'InstanceID'=>0,
			'CurrentURI'=>'<![CDATA['.$url.']]>',
			'CurrentURIMetaData'=>''
		);
		$response = $this->upnp->sendRequestToDevice('SetAVTransportURI',$args,$this->ctrlurl,$type = 'AVTransport');
		$args = array('InstanceID'=>0,'Speed'=>1);
		$this->upnp->sendRequestToDevice('Play',$args,$this->ctrlurl,$type = 'AVTransport');
		return $response;
	}

	//this should be moved to the upnp and renderer model
	public function getControlURL($description_url, $service = 'AVTransport')
	{
		$description = $this->getDescription($description_url);

		switch($service)
		{
			case 'AVTransport':
				$serviceType = 'urn:schemas-upnp-org:service:AVTransport:1';
				break;
			default:
				$serviceType = 'urn:schemas-upnp-org:service:AVTransport:1';
				break;
		}

		foreach($description['device']['serviceList']['service'] as $service)
		{
			if($service['serviceType'] == $serviceType)
			{
				$url = parse_url($description_url);
				return $url['scheme'].'://'.$url['host'].':'.$url['port'].$service['controlURL'];
			}
		}
	}

	public function setNext($url)
	{
		$args = array(
			'InstanceID'=>0,
			'NextURI'=>$url,
			'NextURIMetaData'=>'testmetadata'
		);
		return $this->upnp->sendRequestToDevice('SetNextAVTransportURI',$args,$this->ctrlurl,$type = 'AVTransport');
	}

	public function getState()
	{
		return $this->instanceOnly('GetTransportInfo');
	}

	public function getPosition()
	{
		return $this->instanceOnly('getPositionInfo');
	}

	private function instanceOnly($command,$type = 'AVTransport', $id = 0)
	{
		$args = array(
			'InstanceID'=>$id
		);
		$response = $this->upnp->sendRequestToDevice($command,$args,$this->ctrlurl,$type);
		return $response['s:Body']['u:'.$command.'Response'];
	}

	public function getMedia()
	{
		return $this->instanceOnly('GetMediaInfo');
	}

	public function stop()
	{
		return $this->instanceOnly('Stop');
	}
	
	public function unpause()
	{
		$args = array('InstanceID'=>0,'Speed'=>1);
		return $this->upnp->sendRequestToDevice('Play',$args,$this->ctrlurl,$type = 'AVTransport');
	}

	public function pause()
	{
		return $this->instanceOnly('Pause');
	}

	public function next()
	{
		return $this->instanceOnly('Next');
	}

	public function previous()
	{
		return $this->instanceOnly('Previous');
	}

    public function fforward()
    {
        return $this->next();
    }

    public function rewind()
    {
        return $this->previous();
    }

	public function seek($unit = 'TRACK_NR', $target=0)
	{
		$args = array('InstanceID'=>0,'Unit'=>$unit, 'Target'=>$target);
		$response = $this->upnp->sendRequestToDevice('Seek',$args,$this->ctrlurl.'serviceControl/AVTransport','AVTransport');
		return $response['s:Body']['u:SeekResponse'];
	}

}
