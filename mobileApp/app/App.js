import React, {Component} from 'react';
import {Platform, BackHandler, Alert, Linking, KeyboardAvoidingView, StyleSheet} from 'react-native';
import {compose} from 'recompose';
import {WebView} from 'react-native-webview';
import {AuthContainer} from "./containers";
import firebase from 'react-native-firebase';
import appConfig from './app.json';
import {PermissionsAndroid} from 'react-native';

type Props = {};
class App extends Component<Props> {

    constructor(props) {
        super(props);
        this.state = {
            senderId: appConfig.senderID
        };
        this.onWebViewMessage = this.onWebViewMessage.bind(this);
    }

    async componentWillMount() {
        this.props.init();
    }

    async componentDidMount() {
        this.checkPermission();
        this.createNotificationListeners();
        BackHandler.addEventListener('hardwareBackPress', this.handleBackButton);
    }

    async checkCameraPermission() {
        try {
            const status = await PermissionsAndroid.check(PermissionsAndroid.PERMISSIONS.CAMERA);
            if (!status) {
                this.requestCameraPermission()
            }
        } catch (err) {
            console.warn(err);
            this.requestCameraPermission()
        }
    }

    async requestCameraPermission() {
        try {
            const granted = await PermissionsAndroid.request(
                PermissionsAndroid.PERMISSIONS.CAMERA,
                {
                    title: 'ConvertLead requires Camera Permission',
                    message:
                    'ConvertLead needs access to your camera ' +
                    'so you can take picture to update your profile image.',
                    buttonNeutral: 'Ask Me Later',
                    buttonNegative: 'Cancel',
                    buttonPositive: 'OK',
                },
            );
            if (granted === PermissionsAndroid.RESULTS.GRANTED) {
                console.log('You can use the camera');
            } else {
                console.log('Camera permission denied');
            }
        } catch (err) {
            console.warn(err);
        }
    }

    componentWillUnmount() {
        this.notificationListener();
        this.notificationOpenedListener();
    }

    handleBackButton() {
        Alert.alert(
            'Exit App',
            'Do you want to exit?',
            [
                {text: 'No', onPress: () => console.log('Cancel Pressed'), style: 'cancel'},
                {text: 'Yes', onPress: () => BackHandler.exitApp()},
            ],
            { cancelable: false });
        return true;
    }

    async createNotificationListeners() {
        const channel = new firebase.notifications.Android.Channel('test-channel', 'Test Channel', firebase.notifications.Android.Importance.Max)
            .setDescription('My apps test channel');
        // Create the channel
        firebase.notifications().android.createChannel(channel);

        /*
        * Triggered when a particular notification has been received in foreground
        * */
        this.notificationListener = firebase.notifications().onNotification((notification) => {
            const { title, body } = notification;
            if (Platform.OS === 'android' ) {
                notification
                    .android.setChannelId('test-channel')
                    .android.setSmallIcon('ic_launcher')
                    .setTitle(title)
                    .setBody(body)
                    .setSound("default");
                firebase.notifications().displayNotification(notification);
            } else {
                const notify = new firebase.notifications.Notification()
                    .setNotificationId('notificationId')
                    .setTitle(title)
                    .setBody(body)
                    .setSound("default");
                firebase.notifications().displayNotification(notify)
            }

            console.log("Foreground message", notification);

            if (notification.data) {
                this.goToTargetPage(notification.data);
            }
        });

        /*
        * If your app is in background, you can listen for when a notification is clicked / tapped / opened as follows:
        * */
        this.notificationOpenedListener = firebase.notifications().onNotificationOpened((notificationOpen) => {
            console.log("=============background000==============");
            const { title, body } = notificationOpen.notification;

            console.log("background message when notification is clicked", notificationOpen);

            if (notificationOpen.notification.data) {
                this.goToTargetPage(notificationOpen.notification.data);
            }
        });

        /*
        * If your app is closed, you can check if it was opened by a notification being clicked / tapped / opened as follows:
        * */
        const notificationOpen = await firebase.notifications().getInitialNotification();
        if (notificationOpen) {
            console.log("=============background111==============");
            const { title, body } = notificationOpen.notification;

            console.log("app closed message when notification is clicked", notificationOpen);

            if (notificationOpen.notification.data) {
                this.goToTargetPage(notificationOpen.notification.data);
            }
        }
        /*
        * Triggered for data only payload in foreground
        * */
        this.messageListener = firebase.messaging().onMessage((message) => {
            //process data message
            console.log("Triggered for data only payload in foreground", message);
        });
    }

    //1
    async checkPermission() {
        const enabled = await firebase.messaging().hasPermission();
        if (enabled) {
            this.getToken();
        } else {
            this.requestPermission();
        }
    }

    //3
    async getToken() {
        const fcmToken = await firebase.messaging().getToken();
        if (fcmToken) {
            // user has a device token
            this.props.addDeviceToken(fcmToken);
            console.log("===token===", fcmToken);
        }
    }

    //2
    async requestPermission() {
        try {
            await firebase.messaging().requestPermission();
            // User has authorised
            this.getToken();
        } catch (error) {
            // User has rejected permissions
            console.log('permission rejected');
        }
    }

    onLogin(msgData) {
        console.log("=====> Message Data from webview", msgData);
        this.props.login(msgData.data);
        this.props.registerDeviceToken();
    }

    onLogout(msgData) {
        console.log("=====> Message Data from webview", msgData);
        this.props.logout();
        this.myWebView.injectJavaScript(`document.getElementById('signupLink').removeAttribute("href");`)
    }

    onSignup(msgData) {
        console.log("=====> Message Data from webview", msgData);
        Linking.canOpenURL(msgData.data.url).then(supported => {
            if (supported) {
                Linking.openURL(msgData.data.url);
            } else {
                console.log("Don't know how to open URI: " + msgData.data.url);
            }
        });
    }

    onWebViewMessage(event) {
        console.log("Message received from webview");

        let msgData;
        try {
            msgData = JSON.parse(event.nativeEvent.data);
        } catch (err) {
            console.warn(err);
            return;
        }

        switch (msgData.targetFunc) {
            case "onLogin":
                if (Platform.OS === 'android') {
                    console.log("=================");
                    this.checkCameraPermission();
                }
                this[msgData.targetFunc].apply(this, [msgData]);
                break;
            case "onLogout":
                this[msgData.targetFunc].apply(this, [msgData]);
                break;
            case "onSignup":
                this[msgData.targetFunc].apply(this, [msgData]);
                break;
        }
    }

    render() {
        let jsCode = `
            document.getElementById('signupLink').removeAttribute("href");
        `;
        console.log(this.props.session);
        return (
            <WebView
                source={{uri: 'https://app.convertlead.com'}}
                injectedJavaScript={jsCode}
                ref={webview => {
                    this.myWebView = webview;
                }}
                bounces={false}
                scrollEnabled={true}
                domStorageEnabled={true}
                allowFileAccess={true}
                allowingReadAccessToURL={true}
                allowFileAccessFromFileURLS={true}
                allowUniversalAccessFromFileURLs={true}
                onMessage={this.onWebViewMessage}
                style={{marginTop: 0}}/>
        );
    }

    goToTargetPage(data) {
        let msgData = {
            'title': 'NEW_NOTIFICATION',
            'data': data
        }
        this.myWebView.injectJavaScript(`window.postMessage('${JSON.stringify(msgData)}', '*');`);
    }
}

export default compose(AuthContainer)(App);
