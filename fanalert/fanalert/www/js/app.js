// Ionic Starter App

// angular.module is a global place for creating, registering and retrieving Angular modules
// 'starter' is the name of this angular module example (also set in a <body> attribute in index.html)
// the 2nd parameter is an array of 'requires'
// 'starter.controllers' is found in controllers.js
angular.module('starter', ['ionic', 'starter.controllers', 'ionic.contrib.ui.tinderCards', 'starter.services'])

.run(function($ionicPlatform) {
  $ionicPlatform.ready(function() {
    // Hide the accessory bar by default (remove this to show the accessory bar above the keyboard
    // for form inputs)
    ionic.Platform.fullScreen()
    if (window.cordova && window.cordova.plugins.Keyboard) {
      cordova.plugins.Keyboard.hideKeyboardAccessoryBar(true);
    }
    if (window.StatusBar) {
      // org.apache.cordova.statusbar required
      // StatusBar.styleDefault();
      StatusBar.hide();
    }
    StatusBar.hide();

          Ionic.io();
            var push = new Ionic.Push({
              "debug": true
            });
        
            push.register(function(token) {
              console.log("Device token:",token.token);
            });


/* 
            //push code
            
            var io = Ionic.io();
            var push = new Ionic.Push({
              "onNotification": function(notification) {
                alert('Received push notification!');
              },
              "pluginConfig": {
                "android": {
                  "iconColor": "#0000FF"
                }
              }
            });
            var user = Ionic.User.current();
            
            if (!user.id) {
              user.id = Ionic.User.anonymousId();
            }
            
            // Just add some dummy data..
            user.set('name', 'Bala');
            user.set('bio', 'This is Bala');
            user.save();
           
            var callback = function(data) {
              push.addTokenToUser(user);
              user.save();
              device_token=data.token;
              console.log("Device token:",data.token);
                RequestsService.register(device_token).then(function(response){
                    alert('registered!');
                 });
            };
            push.register(callback);

            //ends push code

            */

  });
})

.config(function($stateProvider, $urlRouterProvider) {
  $stateProvider

  .state('app', {
    url: "/app",
    abstract: true,
    templateUrl: "templates/rubyonic/menu.html",
    controller: 'AppCtrl'
  })


  .state('app.running', {
    url: "/running",
    params: {
        'type':'running'
    },
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/movies-list.html",
        controller: 'MovieCtrl'
      }
    },

  })


  .state('app.upcoming', {
    url: "/upcoming",
    params: {
        'type':'upcoming'
    },
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/movies-list.html",
        controller: 'MovieCtrl'
      }
    },

  })

  .state('app.all', {
    url: "/all",
    params: {
        'type':'all'
    },
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/movies-list.html",
        controller: 'MovieCtrl'
      }
    },

  })



  .state('app.login', {
    url: "/login",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/login.html"
      }
    }
  })

  .state('app.profile', {
    url: "/profile",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/profile.html"
      }
    }
  })

  .state('app.forms', {
    url: "/forms",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/forms.html"
      }
    }
  })

  .state('app.feed', {
    url: "/feed",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/feed.html"
      }
    }
  })

  .state('app.chat-list', {
    url: "/chat-list",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/chat-list.html"
      }
    }
  })

  .state('app.chat-ui', {
    url: "/chat-ui",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/chat-ui.html"
      }
    }
  })

  .state('app.view-post', {
    url: "/view-post",
    views: {
      'menuContent': {
        templateUrl: "templates/rubyonic/view-post.html"
      }
    }
  })

  .state('app.tinder-one', {
    url: "/tinder-one",
    views: {
      'menuContent': {
        templateUrl: "templates/tinder/tinder-one.html"
      }
    }
  })

  .state('app.tinder-two', {
    url: "/tinder-two",
    views: {
      'menuContent': {
        templateUrl: "templates/tinder/tinder-two.html"
      }
    }
  })

  .state('app.tinder-three', {
    url: "/tinder-three",
    views: {
      'menuContent': {
        templateUrl: "templates/tinder/tinder-three.html"
      }
    }
  })

  .state('app.tinder-four', {
    url: "/tinder-four",
    views: {
      'menuContent': {
        templateUrl: "templates/tinder/tinder-four.html"
      }
    }
  })
  
  // if none of the above states are matched, use this as the fallback
  $urlRouterProvider.otherwise('/app/running');
});
