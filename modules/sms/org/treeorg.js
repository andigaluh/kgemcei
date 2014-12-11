
var OrgCookieStatus = new Array();
var OrgCookieName = "orgX";
var OrgBranchStatus = new Array();

function OrgSaveExpandedStatus (nodeID, expanded) {
      OrgCookieStatus[nodeID] = expanded;
      OrgSaveCookie();
}

function OrgSaveCookie () {
   var cookieString = new Array();

   for (var i in OrgCookieStatus) {
      if (OrgCookieStatus[i] == true) {
         cookieString[cookieString.length] = i;
      }
   }
   
   document.cookie = OrgCookieName + '=' + cookieString.join(':');
}

function OrgLoadCookie () {
   var cookie = document.cookie.split('; ');
   for (var i=0; i < cookie.length; i++) {
      var crumb = cookie[i].split('=');
      if (OrgCookieName == crumb[0] && crumb[1]) {
         var expandedBranches = crumb[1].split(':');
         for (var j=0; j<expandedBranches.length; j++) {
            OrgCookieStatus[expandedBranches[j]] = true;
         }
      }
   }
}

function OrgResetBranches () {
   OrgLoadCookie();
   for (var nodeID in OrgCookieStatus) {
      if(OrgCookieStatus[nodeID]) {
         OrgToggleBranch(nodeID);
      }
   }
}



function OrgToggleBranch (nodeID) {

   var currentBlock   = document.getElementById(nodeID);
   if(!currentBlock) {
      return;
   }
   var newDisplay     = (currentBlock.style.display == 'inline' ? 'none' : 'inline');

   currentBlock.style.display = newDisplay;
   OrgBranchStatus[nodeID] = !OrgBranchStatus[nodeID];
   OrgSaveExpandedStatus(nodeID, OrgBranchStatus[nodeID]);

   // Swap image
   OrgSwapImage(nodeID);
   return false;
}


function OrgSwapImage (nodeID) {
   if(!(document.images['img_' + nodeID])) return;
   imgSrc = document.images['img_' + nodeID].src;
   
   re = /^(.*)(collapsed|expanded)(bottom|top|single)?.gif$/
   if (matches = imgSrc.match(re)) {
      document.images['img_' + nodeID].src = OrgStringFormat('{0}{1}{2}{3}',
                                                matches[1],
                                                matches[2] == 'collapsed' ? 'expanded' : 'collapsed',
                                                matches[3] ? matches[3] : '',
                                                '.gif');
   }
}

function OrgStringFormat (strInput)
{
   var idx = 0;

   for (var i=1; i<arguments.length; i++) {
      while ((idx = strInput.indexOf('{' + (i - 1) + '}', idx)) != -1) {
         strInput = strInput.substring(0, idx) + arguments[i] + strInput.substr(idx + 3);
      }
   }
   
   return strInput;
}

