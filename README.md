# Introduction to Events world, CQRS and Event Sourcing

## Domain

We are going to implement very small finance domain, usage of prepaid card, to show possible applications of CQRS and Event Sourcing techniques.

Application should allow to:

- register new prepaid card with specific number and currency
- load some funds into card 
- pay for something
- changing daily card limit expressed as total amount of transactions
- block and unblock card
- deregister card, if there are no funds on the card

Payments are very important operations, they should be executed when:

- card has sufficient funds
- daily card's limit will not be exceeded 
- card is not blocked

*Rules may change in future, you can try to implement Specification pattern here (or similar solution) to achive very extensible design. This is not a part of our workshop, but definitely worth to check.* 

*Example of extra rule: we should reject transactions if card was used in more than one place in very short period of the time.*  

## Task
 
Application will be implemented with [Prooph](http://getprooph.org/) components (packages links below). Some basic classes and setups will be delivered, in first version we will be able to run code directly from CLI.

- [prooph/event-sourcing](https://github.com/prooph/event-sourcing)
- [prooph/event-store](https://github.com/prooph/event-store)
- [prooph/event-store-bus-bridge](https://github.com/prooph/event-store-bus-bridge)
- [prooph/event-store-doctrine-adapter](https://github.com/prooph/event-store-doctrine-adapter)
- [prooph/service-bus](https://github.com/prooph/service-bus)

Some extra requirements should be implemented too:

- view of all cards with informations about current balances, number of performed transactions, limits, etc., as a separate table or text file
- view of all accepted transactions, as a separate table or text file

## Remarks

In production system you should write unit and acceptance tests, use integrations with frameworks and probably few more things... During the workshop we would like to focus on Events, CQRS and Event Sourcing, so this time and only this time, we can do it differently.  
 
 
 

