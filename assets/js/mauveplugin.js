
/** Hides up/down arrow when clicking on Web developer tab */
jQuery(document).ready(function($) {
  
  $('.mauveplugin_ShowMore').on('click', function(){
    $id_tec=$(this).attr('data-technique');
    mauveplugin_displaylist($id_tec, this);
  });

  $('.mauveplugin_eye').on('click', function(){
    $path=$(this).attr('data-path');
    $id_tech=$(this).attr('data-technique');
    mauveplugin_showerr($path, this, $id_tech);
  });

  $('.mauveplugin_code').on('click', function(){
    $id_el=$(this).attr('data-id');
    $id_tech=$(this).attr('data-technique');
    mauveplugin_OpenCode($id_el, $id_tech);
  });

  $('#mauveplugin_show_btn').on('click', function(){
    mauveplugin_ViewResults();
  });

  $('#mauveplugin_source_code').on('click', function() {

    $("#mauveplugin_btn_up, #mauveplugin_btn_down").remove();
  });

  $('.mauveplugin_help').hover( function() {
    $(this).prev().css( "visibility","visible" );
   }, function() {
    $(this).prev().css( "visibility","hidden" );
  });
  

  
});

/** Creates and displays the re-evaluation box when the WordPress update button is clicked */
jQuery(document).on('click', '.editor-post-publish-button, .editor-post-save-draft', function() {
  var mauvebtn=document.getElementById("mauveplugin_btn");
  if (mauvebtn.style.display === 'none'){
    var sidebar=document.createElement("div");
    sidebar.setAttribute("id","mauveplugin_sidebar");
    var sideheader=document.createElement("p");
    sideheader.setAttribute("id","mauveplugin_sidebarHeader");
    var close=document.createElement("a");
    close.setAttribute("href","javascript:void(0)");
    close.setAttribute("class","mauveplugin_closebtn");
    close.textContent="X";
    var title=document.createElement("h2");
    title.textContent="NEW EVALUATION";
    sideheader.appendChild(close);
    sideheader.appendChild(title);
    sidebar.appendChild(sideheader);
    var parsidebar=document.createElement("p");
    parsidebar.setAttribute("id","mauveplugin_parsidebar");
    var textsidebar=document.createTextNode("From this section you can evaluate your updated content whenever you want!");
    parsidebar.appendChild(textsidebar);
    sidebar.appendChild(parsidebar);
    var form=document.getElementById("mauveplugin_Form");
    sidebar.appendChild(form);
    mauvebtn.style.display="block";
    var main=document.createElement("div");
    main.setAttribute("id","mauveplugin_hamb");
    var hamb=document.createElement("button");
    hamb.setAttribute("class","mauveplugin_openbtn");
    hamb.addEventListener('click', function(){
      main.style.display='none';
      sidebar.style.display='block';
    });
    close.addEventListener('click',function(){
      sidebar.style.display='none';
      main.style.display='block';
    })
    main.appendChild(hamb);
    document.body.appendChild(main);
    main.parentNode.insertBefore(sidebar,main);

    var highlightedElements = document.querySelectorAll('.mauveplugin_blink');
    highlightedElements.forEach(function(elem) {
        elem.classList.remove('mauveplugin_blink');
    });

  }
  
});

/** Hides the MAUVE button and re-evaluation box when the edit post toolbar is clicked and restore the visibility after that */

var targetNode = document.querySelector('body');

if(targetNode){
  var observer = new MutationObserver(function(mutationsList) {
    for (var mutation of mutationsList) {
      if (mutation.addedNodes && mutation.addedNodes.length > 0) {
        var addedNode = mutation.addedNodes[0];
        if (addedNode.classList && addedNode.classList.contains('interface-interface-skeleton__secondary-sidebar')) {
          var mauvebtn = document.getElementById('mauveplugin_btn');
          var hamb=document.getElementById('mauveplugin_hamb');
          var sidebar=document.getElementById('mauveplugin_sidebar');
          if (mauvebtn || hamb || sidebar) {
            mauvebtn.style.display = 'none';
            hamb.style.display = 'none';
            sidebar.style.display = 'none';
          }
        }
      }
      if (mutation.removedNodes && mutation.removedNodes.length > 0) {
        var removedNode = mutation.removedNodes[0];
        if (removedNode.classList && removedNode.classList.contains('interface-interface-skeleton__secondary-sidebar')) {
          var mauvebtn = document.getElementById('mauveplugin_btn');
          var hamb=document.getElementById('mauveplugin_hamb');
          var sidebar=document.getElementById('mauveplugin_sidebar');
          var evaluationSum= document.getElementById("mauveplugin_AccessibilityEvaluation");
          if (hamb || sidebar || !evaluationSum) {
            mauvebtn.style.display = 'block';
            hamb.style.display = 'block';
  
          }
        }
      }
    }
  });
  
  var config = { childList: true, subtree: true };
  
  observer.observe(targetNode, config);
  
}

/** Enables switching between Evaluation and Web developer tabs */

jQuery(document).on('click', '.mauveplugin_summary', function(event) {
    event.preventDefault();
    const tabId = this.getAttribute('href');
    const tabContent = document.querySelector(tabId);
  
    jQuery('.mauveplugin_summary, .mauveplugin_tab').removeClass('active');
  
    
    this.classList.add('active');
    tabContent.classList.add('active');

    jQuery("#mauveplugin_next_result").css('display','none');
    jQuery("#mauveplugin_prev_result").css('display','none');
    var shadows=document.getElementsByClassName("mauveplugin_shadow");
    for(var i=0; i<shadows.length; i++){
      shadows[i].classList.remove('mauveplugin_shadow');
    }
    
    document.getElementById("mauveplugin_AccessibilityEvaluation").scrollTo(0,0);
});

/** Show error on page */

function mauveplugin_showerr(path,inputel,tech){


  const lastSlashIndex = path.lastIndexOf('/');
  const secondLastIndex = path.indexOf('/body');
  
  if (lastSlashIndex !== -1 && secondLastIndex !== -1) {
    var substring=path.substring(secondLastIndex+6, lastSlashIndex+1);
    var sub=path.substring(lastSlashIndex+1);
  } 
  else{
    return null;
  }


  if(substring == "" ){
    var element = document.evaluate("//div[contains(@class, 'is-root-container')]/"+sub, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
  }
  else{
      var element = document.evaluate("//div[contains(@class, 'is-root-container')]/"+substring+"/"+sub, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;

  }
 
  var highlightedElements = document.querySelectorAll('.mauveplugin_blink');
  highlightedElements.forEach(function(elem) {
      elem.classList.remove('mauveplugin_blink');
  });
  for (var key in Techniques){
    if(tech.startsWith(key)){
      var href=Techniques[key]+tech;
    }
  }

  if (element) {
    element.classList.add('mauveplugin_blink');
    element.scrollIntoView({ behavior: "smooth", block: "center" });

    var down=document.createElement('a');
    down.href="#" + tech;
    down.title="Go to bottom";
    down.setAttribute("id","mauveplugin_btn_down");
    var arrow=document.createElement("i");
    arrow.setAttribute("class","mauveplugin_material-icons");
    arrow.textContent="arrow_downward";
    down.appendChild(arrow);
    document.getElementById("editor").appendChild(down);
    down.addEventListener('click',function(){
      document.getElementById("editor").removeChild(down);
    })

    if (!inputel.hasAttribute('data-clicked')) {
    
      var containerDone=document.createElement("div");
      var pDone=document.createElement("p");
      var textDone=document.createTextNode("Viewed");
      pDone.appendChild(textDone);
      var done=document.createElement("img");
      done.src= mauveplugin_vars.image_url;
      containerDone.appendChild(pDone);
      containerDone.appendChild(done);
      
      containerDone.setAttribute("id","mauveplugin_done");

      inputel.parentNode.insertBefore(containerDone,inputel);

      inputel.setAttribute('data-clicked', true);
    };
  } else if (element == null || element.style.display == 'none'){
    alert("Element cannot be found on the page. Please search for the element at the code line");
  }

  element.addEventListener('click',()=>element.classList.remove('mauveplugin_blink'));

  
}

/** Show error on code */

function mauveplugin_OpenCode(id,tech){
    const devLink = document.querySelector('#mauveplugin_source_code'); 
        
    devLink.click();
    
    var Results = document.getElementsByClassName("mauveplugin_MAUVEresult");
    for(var i=0; i<Results.length;i++){
      Results[i].classList.remove('mauveplugin_shadow');
    }
  
    var element = document.getElementById('mauveplugin_line' + id + tech);

    if (element) {
        element.style.boxShadow="5px 13px 15px #888888";
        setTimeout(function() {
            element.style.boxShadow="none";
          }, 10000); 
        element.scrollIntoView({ behavior: "smooth", block: "center" });
        var sibling = element.nextSibling;
        while (sibling) {
          if (sibling.nodeType === 1 && sibling.tagName === 'P' && !sibling.classList.contains('mauveplugin_MAUVEresult')) {
            
            sibling.style.border="2px solid #330e3e";
            sibling.style.padding="10px";
            setTimeout(function() {
              sibling.style.border = 'none';
              sibling.style.padding= '0px';
            }, 10000); 
            break;
          }
          sibling = sibling.nextSibling;
        }
        
        var TopPos=element.offsetTop;
        var back=document.getElementById("mauveplugin_back");
        if(back.style.display ='none'){
            back.style.display='block';
            back.style.top=TopPos;
            var next=document.getElementById("mauveplugin_next_result").style.display='none';
            var prev=document.getElementById("mauveplugin_prev_result").style.display='none';
            var shadows=document.getElementsByClassName("mauveplugin_shadow");
            for(var i=0; i<shadows.length; i++){
              shadows[i].classList.remove('mauveplugin_shadow');
            }
        }
        else{
            back.style.display='none';
        }
        back.addEventListener('click', function(event) {
            event.preventDefault(); 
            
            var Summary=document.getElementById("mauveplugin_summary_table");
            Summary.classList.add('active');
            const tabId = Summary.getAttribute('href');
            const tabContent = document.querySelector(tabId);
            tabContent.classList.add('active');

            document.body.scrollTop = 0;
            
        });

        jQuery(document).on('click', '#mauveplugin_back', function(event) {
            event.preventDefault();
          
           
            jQuery('.mauveplugin_summary, .mauveplugin_tab').removeClass('active');
          
            var Summary=document.getElementById("mauveplugin_summary_table");
            Summary.classList.add('active');
            const tabId = Summary.getAttribute('href');
            const tabContent = document.querySelector(tabId);
            tabContent.classList.add('active');
            back.style.display='none';
            element.classList.remove('mauveplugin_shadow');
            sibling.style.border="none";
            sibling.style.padding="0px";
            
            Summary.scrollIntoView({ behavior: 'smooth' });
          });
       
    }
}

/** Expand and resizes error details */

function mauveplugin_displaylist(id,viewbtn){
    var node=document.getElementById(id);
    if(node.style.display == '' || node.style.display == 'none'){
        node.style.display='block';
        viewbtn.innerHTML="Show less";
        viewbtn.style.border='2px solid #4f94d4';
        viewbtn.style.color='#4f94d4';
    }
    else{
        node.style.display='none';
        viewbtn.innerHTML="View more";
        viewbtn.style.border='2px solid #330e3e';
        viewbtn.style.color='#330e3e';
    }
}

/** Allows scrolling through all error lines on code*/
function mauveplugin_ViewResults() {
  var Results = document.getElementsByClassName("mauveplugin_MAUVEresult");
  var next = document.getElementById("mauveplugin_next_result");
  var prev = document.getElementById("mauveplugin_prev_result");
  var back = document.getElementById("mauveplugin_back");
  var i = 0;

  back.style.display = 'none';
  next.style.display = 'block';
  prev.style.display = 'none';

  function mauveplugin_highlightResult(i) {
      for (var j = 0; j < Results.length; j++) {
          Results[j].classList.remove('mauveplugin_shadow');
      }
      Results[i].scrollIntoView({ behavior: "smooth", block: "center" });
      Results[i].classList.add('mauveplugin_shadow');
      next.style.top = Results[i].offsetTop - 50 + "px";
      prev.style.top = Results[i].offsetTop - 50 + "px";
  }

  next.addEventListener('click', function() {
      i++;
      if (i >= Results.length) {
          i = 0;
      }
      mauveplugin_highlightResult(i);
      prev.style.display = 'block';
      
  });

  prev.addEventListener('click', function() {
      i--;
      if (i < 0) {
          i = Results.length - 1;
      }
      mauveplugin_highlightResult(i);
      
  });

 
  if (Results.length > 0) {
    mauveplugin_highlightResult(0);
  }

  var up=document.createElement('a');
  up.href="#mauveplugin_Code";
  up.title="Go to top";
  up.setAttribute("id","mauveplugin_btn_up");
  var arrow=document.createElement("i");
  arrow.setAttribute("class","mauveplugin_material-icons");
  arrow.textContent="arrow_upward";
  up.append(arrow);
  document.getElementById("mauveplugin_Code").appendChild(up);
  up.addEventListener('click',function(){
      document.getElementById("mauveplugin_Code").removeChild(up);
  })


  var codeDiv = document.getElementById("mauveplugin_Code");

  codeDiv.addEventListener("scroll", function() {
      if (codeDiv.scrollTop === 0) {
          window.scrollTo(0, 0);
      }
  });
}

const Techniques={
  "ARIA":"https://www.w3.org/WAI/WCAG21/Techniques/aria/",
  "SCR":"https://www.w3.org/WAI/WCAG21/Techniques/client-side-script/",
  "C":"https://www.w3.org/WAI/WCAG21/Techniques/css/",
  "F":"https://www.w3.org/WAI/WCAG21/Techniques/failures/",
  "G":"https://www.w3.org/WAI/WCAG21/Techniques/general/",
  "H":"https://www.w3.org/WAI/WCAG21/Techniques/html/",
  "PDF":"https://www.w3.org/WAI/WCAG21/Techniques/pdf/",
  "SVR":"https://www.w3.org/WAI/WCAG21/Techniques/server-side-script/",
  "SL":"https://www.w3.org/WAI/WCAG21/Techniques/silverlight/",
  "SM":"https://www.w3.org/WAI/WCAG21/Techniques/smil/",
  "T":"https://www.w3.org/WAI/WCAG21/Techniques/text/"
};










