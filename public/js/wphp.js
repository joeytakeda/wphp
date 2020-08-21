/* Special javascript handling for the blog */


makeImagesResponsive();
addBreaksToSlashes();

/*
    Function to make the images responsive
 */
function makeImagesResponsive(){
    let images = document.querySelectorAll('img');
    images.forEach(img => {
        if (img.style.width){
            let width = img.style.width;
            img.style.removeProperty('width');
            img.style.removeProperty('height');
            img.style.maxWidth = width;
        }
    });
}

/*

    Small function to add zero-width spaces to slashed strings.
 */
function addBreaksToSlashes(){
    let slashEls = document.querySelectorAll('label, a[href]');
    let slashRex = new RegExp('/','g');
    slashEls.forEach(el =>{
        el.childNodes.forEach(node =>{
            if (node.nodeType === 3 && slashRex.test(node.textContent)){
                node.textContent = node.textContent.replace(slashRex, '/\u200b');
            }
        })

    })
}