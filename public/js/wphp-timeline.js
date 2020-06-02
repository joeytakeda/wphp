/* 
 * A module for creating the timeline visualizations for WPHP.
 * 
 */
 
 const baseuri = window.location.href.split('#')[0];
 const titleTable = document.getElementById('titleRoles');
 let initJson = {};
 let timelineJson = {};
 let storymapJson = {};
 let wikiResults = {};
 let vizDiv = document.getElementById('viz');
 let storymap;
 
 fetch(baseuri +'/timeline')
    .then(response => response.json())
    .then(data => {
    initJson = data;
            makeTimelineJson();
        makeStorymapJson();
        document.getElementById('vizNav').classList.add('enabled');
        document.getElementById('timeline-link').addEventListener('click',function(a){
            a.preventDefault();
            vizDiv.classList.remove('storymap');
            vizDiv.classList.add('timeline');
            window.removeEventListener('resize',updateStorymap,true);
            if (!document.getElementById('timeline').classList.contains('tl-timeline')){
                            new TL.Timeline('timeline', timelineJson);   
            }

            });
            
            document.getElementById('storymap-link').addEventListener('click', function(a){
            a.preventDefault();
            vizDiv.classList.remove('timeline');
            vizDiv.classList.add('storymap');
            if (!document.getElementById('storymap').classList.contains('vco-storymap')){
                   storymap = new VCO.StoryMap('storymap', storymapJson,{});

            }
            window.addEventListener('resize', updateStorymap, true);
        });
        });
 
 
 
 function updateStorymap(){
     storymap.updateDisplay();
 }


/* 
 * Utility function to return the split date array from 
 * a date
 * 
 */

 


 function makeTimelineJson(){

    /* If the the timeline JSON object hasn't already been made... */
    if (Object.keys(timelineJson).length === 0){
    
        /* Set up some variables */
        let person = initJson['person'];
        let contributions = initJson['contributions'];
        let birthArr = returnDate(person,'dob');
        let deathArr = returnDate(person,'dod');
        
        
        /* And initialize some of the arrays */
        timelineJson.events = [];
        timelineJson.title = {};
        timelineJson.title.text= {};
        
        /* Start making stuff  */
        
        /* Create the headline, which is the persons first and last name */
        timelineJson.title.text.headline = person.firstName + " " + person.lastName;
        
        /* Now add the Wikipedia URL */
        if (person.hasOwnProperty('wikipediaUrl')){
            timelineJson.title.media={};
            timelineJson.title.media.url = person.wikipediaUrl; 
        }
        
        /* Now create the titles bit */
        for (const c in contributions){
            let titleObj = contributions[c];
            let thisEventArr = [];
            if ( titleObj.hasOwnProperty('pubDate') && titleObj['pubDate'].length > 0 && titleObj['pubDate'].match(/^\d+$/g)){
                thisEventArr.start_date = {};
                let pubArr = returnDate(titleObj, 'pubDate');
                constructDateObj(thisEventArr.start_date, pubArr);
                let genre = getGenre(titleObj);
                thisEventArr.group = genre[0];
                thisEventArr.text ={};
                thisEventArr.text.headline = `<span data-genre="${genre[1]}">${titleObj['title']}</span>`;
                if ((deathArr !== null)){
                    let age = ageDiff(birthArr, returnDate(titleObj,'pubDate'));
                    let posthumous = pubArr[0] > deathArr[0];
                    let subtitleText;
                    if (posthumous){
                        subtitleText = "Published posthumously (" + age +").";
                    } else {
                        subtitleText = age;
                    }
                    thisEventArr.text.text = subtitleText + " " + `<a href="${baseuri.split('person')[0] + 'title/' + c}">View Record</a>`;
                }
                timelineJson.events.push(thisEventArr);
                }
            }
            
       timelineJson.title.text.text = "Author of " + timelineJson.events.length + " titles";
            
       /* And add birth and date date events if we can */   
       
        let milestones = ['dob','dod'];
        for (const milestone in milestones){
            let prop = milestones[milestone];
            if (person.hasOwnProperty(prop)){
                thisEventArr = [];
                thisEventArr.start_date = {};
               thisEventArr.text = {};
                constructDateObj(thisEventArr.start_date, returnDate(person, prop));
                let headlineText = (prop == 'dod') ? 'Died' : 'Born';
                let cityProp = (prop == 'dod') ? 'cityOfDeath' : 'cityOfBirth';
  
               if (prop == 'dod'){
                   headlineText += " " + ageDiff(birthArr, deathArr) +""
               };
                if (person.hasOwnProperty(cityProp)){
                    headlineText += " in " + person[cityProp];
                }
                thisEventArr.text.headline = headlineText;
                timelineJson.events.push(thisEventArr);
            }
        }
            
            
            
    }
 }
 
 
 function getGenre(obj){
    let genreStr = (obj.hasOwnProperty('genre')) ? capitalize(obj['genre']) : 'Unknown';
    let genreNorm = genreStr.replace(/[\s-:,\.\/]*/g,'');
    return new Array(genreStr, genreNorm);
 }
 
 /*
  * Function to create the date object for the timeline JS 
  * from a date object
  * 
  * 
  */
 function constructDateObj(source, dateArray){
   for (var i=0; i < 3; i++){
      let val = dateArray[i];
      if (val == 0){
        return;
       }  
       if (i == 0){
         source.year=val;
        } else if (i == 1){
         source.month=val;
       } else {
         source.day=val;
      }          
    }
 }
 
 /*
  * Get the difference between two dates
  * and renders the difference as an age string (d2 - d1)
  * 
  * d1 {array}
  * d2 {array}
  */
 function ageDiff(d1, d2){
    let granularity;
    let precision = true;
    let diff = d2[0] - d1[0];
    
    /* Now determine the granularity: if both dates have the same
     * field that's not 0, then that's the best we can do */
    for (let i = 0; i<3; i++){
        if (d1[i] > 0 && (d2[i] > 0)){
            granularity = i;
        }
    }
    
    /* If the granularity is 0 (i.e. a year),
     * then we just know that the precision is false */
    if (granularity == 0){
        precision = false;
    } else 
        /* If the granularity is at the month level, then we have a chance at
         * knowing whether or not we have a precise age */
        if (granularity == 1){
            
            /* If it's the same month, then we just don't know what their age is */
            if ((d2[1] - d1[1]) == 0){
                precision = false;
            } else {
            /* Otherwise, we can figure it out */
                if ((d2[1] - d1[1]) < 0){
                    diff = diff -1;
            }
        }
     } else {
         if (!(d2[1] - d1[1] > 0 || (d2[1] == d1[1] && d2[2] > d1[2]))){
            precision = false;
         }
     }
     
     
    if (precision){
        return "Age: " + diff;
    } else {
        return "Appromiate age: " + (diff - 1) + "/" + diff;
    }

 }
 
 
 function returnDate(arr, key){
    return (arr.hasOwnProperty(key)) ? arr[key].split('-').map(x => parseInt(x)) : null;
}


 /* 
  * Utility function to capitalize a string
  */
 function capitalize(str){
    return str.charAt(0).toUpperCase() + str.slice(1);
 };
 
 
 
 
function makeStorymapJson(){

    
     storymapJson = {
        "storymap":
            {"slides":[{"type":"overview","text":{"headline":"Charlotte Susan Maria Campbell Bury","text":"Author of 14 titles"}},{"location":{"lat":"55.9520600","lon":"-3.1964800","line":"false","icon":"http:\/\/maps.gstatic.com\/intl\/en_us\/mapfiles\/ms\/micons\/blue-pushpin.png"},"text":{"headline":"1797–1822","text":"<div><p>Published 3 titles.<\/p><ol><li><span style=\"font-weight:bold;\">1797<\/span>: Poems on several occasions. By a lady.<\/li><li><span style=\"font-weight:bold;\">1812<\/span>: Self-Indulgence. A Tale of the Nineteenth Century. In two volumes.<\/li><li><span style=\"font-weight:bold;\">1822<\/span>: Conduct is Fate. In Three Volumes.<\/li><\/ol><\/div>"}},{"location":{"lat":"51.5085300","lon":"-0.1257400","line":"false","icon":"http:\/\/maps.gstatic.com\/intl\/en_us\/mapfiles\/ms\/micons\/blue-pushpin.png"},"text":{"headline":"1826–1835","text":"<div><p>Published 11 titles.<\/p><ol><li><span style=\"font-weight:bold;\">1826<\/span>: \"Alla Giornata;\" or, To the Day. In Three Volumes.<\/li><li><span style=\"font-weight:bold;\">1826<\/span>: Suspirium Sanctorum; or, Holy Breathings. A series of prayers for every day in the month<\/li><li><span style=\"font-weight:bold;\">1827<\/span>: Flirtation. A Novel. In three volumes.<\/li><li><span style=\"font-weight:bold;\">1828<\/span>: A Marriage in High Life. Edited by the authoress of 'Flirtation.' In two volumes.<\/li><li><span style=\"font-weight:bold;\">1828<\/span>: Flirtation. A Novel. Second edition. In three volumes.<\/li><li><span style=\"font-weight:bold;\">1829<\/span>: The Casket, a Miscellany, Consisting of Unpublished Poems<\/li><li><span style=\"font-weight:bold;\">1830<\/span>: Journal of the Heart. Edited by the authoress of \"Flirtation.\"<\/li><li><span style=\"font-weight:bold;\">1830<\/span>: The Exclusives<\/li><li><span style=\"font-weight:bold;\">1833<\/span>: The Three Great Sanctuaries of Tuscany, Valombrosa, Camaldoli, Laverna: a Poem, with Historical and Legendary Notices. By the Right Honourable Lady Charlotte Bury.<\/li><li><span style=\"font-weight:bold;\">1834<\/span>: Flirtation. In three volumes.<\/li><li><span style=\"font-weight:bold;\">1835<\/span>: Heath's Book of Beauty. 1835. With nineteen beautifully finished engravings, from drawings by the first artists. Edited by the Countess of Blessington.<\/li><\/ol><\/div>"}}]}
      };
}
 
 
 /* 
  * Function to override the arbitrary width of the slide content (it's set to something
  * like 525px, but it can just be 100%)
  * 
  */
 function removeArbitraryWidth(){
     let slideTexts = document.querySelectorAll('.tl-slide-content');
     slideTexts.forEach(function(t){
         t.style.width = '100%';
     });
 }
 
 
 /* 
  * This moves the normalized genre up to the closest tl-timemarker
  * (We could probably make this a bit nicer...
  * 
  * 
  */
function addStylingToTimeline(){
     let genreSpans = document.querySelectorAll("span[data-genre");
     genreSpans.forEach(function(s){
         let genre = s.getAttribute('data-genre');
         let el = s.closest('.tl-timemarker');
         if (el){
             el.setAttribute('data-genre',genre);
         }
     });
  }
  
  // set up the mutation observer
var observer = new MutationObserver(function (mutations, me) {
  var canvas = document.querySelectorAll('.tl-timemarker')[0];
  if (canvas) {
    addStylingToTimeline();
    removeArbitraryWidth();
    me.disconnect(); // stop observing
    return;
  }
});



// start observing
observer.observe(document, {
  childList: true,
  subtree: true
});
