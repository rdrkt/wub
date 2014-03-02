Wub is a Work Buffer
====================

__Wub__ is a simple to use, "thread-safe" PHP library which provides a an interface with Redis to allow you to schedule nonessential processing and storage of data. 

###Completely Contrived Use Case #1###

  - Problem:
    + You run a website, Joe's Book and Jelly Bean Emporium
    + You own and manage all your book stock, but you outsource Jelly Bean delivery through a third party API.
    + Due to contractual obligations you are stuck with your third party API (#sadface.), and it's __slow__. 
    + You can process your book orders in less than 20ms using the latest tech, but if your customers ordered a Jelly Bean, calls to their api can take up to 60 seconds!
    + Even though you're able to process the order in mere split-seconds, for the end-use, they become frustrated at the delay to complete their order processing.
  - Solution:
    + When the user clicks order, instead of calling the Jelly Bean API, you can quickly queue the order and give the user their order number.
    + Write a simple Worker which sits in the background and processes calls to the Jelly Beans API as they arrive, and e-mail the user a confirmation when the API is called successfully.
    + ...Profit!
