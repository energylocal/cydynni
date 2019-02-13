/**
 * Get the user IP throught the webkitRTCPeerConnection
 * @param onNewIP {Function} listener function to expose the IP locally
 * @return undefined
 */
function getUserIP(onNewIP) {
  //  onNewIp - your listener function for new IPs
  //compatibility for firefox and chrome
  var myPeerConnection =
    window.RTCPeerConnection ||
    window.mozRTCPeerConnection ||
    window.webkitRTCPeerConnection
  var pc = new myPeerConnection({
      iceServers: []
    }),
    noop = function() {},
    localIPs = {},
    ipRegex = /([0-9]{1,3}(\.[0-9]{1,3}){3}|[a-f0-9]{1,4}(:[a-f0-9]{1,4}){7})/g,
    key

  function iterateIP(ip) {
    if (!localIPs[ip]) onNewIP(ip)
    localIPs[ip] = true
  }

  // @todo: might not be supported by ie? needs testing.
  //create a bogus data channel
  pc.createDataChannel('')

  // create offer and set local description
  pc.createOffer()
    .then(function(sdp) {
      sdp.sdp.split('\n').forEach(function(line) {
        if (line.indexOf('candidate') < 0) return
        line.match(ipRegex).forEach(iterateIP)
      })

      pc.setLocalDescription(sdp, noop, noop)
    })
    .catch(function(reason) {
      // An error occurred, so handle the failure to connect
    })

  //listen for candidate events
  pc.onicecandidate = function(ice) {
    if (
      !ice ||
      !ice.candidate ||
      !ice.candidate.candidate ||
      !ice.candidate.candidate.match(ipRegex)
    )
      return
    ice.candidate.candidate.match(ipRegex).forEach(iterateIP)
  }
}

var min = 2 // lowest ip in subnet
var max = 253 // highest ip in subnet
var group = 10 // group requests in batches
var interval = null // stop attempting to scan once finished

// jquery loaded....
$(function() {

  getUserIP(function(ip) {
    document.querySelector('#ip').innerText = 'Your local IP: ' + ip
    findSubnetNodes(ip)
  })

  function findSubnetNodes(ip) {
    var subnet = ip
      .split('.')
      .slice(0, -1)
      .join('.')
    var ips = []
    // create list of ips to scan
    for (i = min; i <= max; i++) {
      ips.push([subnet, i].join('.'))
    }
    // scan the ips in groups
    for (j = 0; j < ips.length; j += group) {
        // @todo: add pause here to delay groups of ajax requests.
        // setTimeout(function(){
        //     findNodesInList(ips.slice(j, j + group))
        // }, 300)
        findNodesInList(ips.slice(j, j + group))
    }
  }

  var found = []
  var failed = []
  var requests = []
  var responses = []

  function findNodesInList(ips) {
    var promises = []
    for (index in ips) {
      var ip = ips[index]

      // count reqests
      requests.push(getVersion(ip))
    }
  }

  function getVersion(ip) {
    var msg = ''
    var url = 'http://' + ip + '/emoncms/describe'
    var request = $.ajax({
      url: url,
      timeout: 600,
      dataType: 'text'
    })
      .done(function(result) {
        found.push({ ip: ip, result: result.trim() })
      })
      .fail(function(xhr, error, message) {
        // failed.push({ message: message, ip: ip, error: error })
        return false
      })
      .always(function(data) {
        // count responses
        responses.push(data)
      })

    // return the Deferred
    return request
  }
  //request.abort();

  $(document).ajaxComplete(function() {
    if (responses.length === requests.length) {
      
      // print status
      $('#loader').text('Finished searching ' + requests.length + ' nodes');

      if (found.length > 0) {
        document.getElementById('found').classList.remove('d-none');
        document.getElementById('not-found').classList.add('d-none');
      } else {
        document.getElementById('found').classList.add('d-none');
        document.getElementById('not-found').classList.remove('d-none');
      }

      // print list
      var template = document.getElementById('list-group-item')
      var listItems = document.createElement('div')
      for (index in found) {
        let f = found[index]
        let listItem = document.importNode(template.content, true)
        let link = listItem.querySelector('a')
        let img = link.querySelector('img');
        let label = link.querySelector('span');
        let linkPath = '';
        let linkIcon;
        switch(f.result) {
            case 'emonbase':
            linkIcon = 'img/base.png';
            break;
            case 'emonpi':
            linkIcon = 'img/emonpi.png';
            break;
            case 'smartplug':
            linkIcon = 'img/plug.png';
            break;
            default:
            linkIcon = 'img/node.png';
        }
        
        devicename = f.result;
        if (devicename=="emonbase") devicename = "hub";
        label.innerText = devicename + ' found at: ' + f.ip;
        img.src = PATH + 'Modules/find/' + linkIcon;
        link.href = 'http://' + f.ip + linkPath

        listItems.appendChild(listItem)
      }

      document.querySelector('#list').innerHTML = listItems.innerHTML;
      window.clearInterval(interval); // stop scanning for new nodes
    }
  })
})
