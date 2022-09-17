/**
 * @format
 */

import React from 'react'
import {AppRegistry} from 'react-native';
import {Provider} from 'react-redux';
import {store} from './app/containers';
import App from './app/App';
import {name as appName} from './app/app.json';
import bgMessaging from './app/bgMessaging';

const ReduxApp = () => (
    <Provider store={store}>
        <App />
    </Provider>
)

AppRegistry.registerComponent(appName, () => ReduxApp);
AppRegistry.registerComponent('ReactNativeFirebaseDemo', () => bootstrap);
AppRegistry.registerHeadlessTask('RNFirebaseBackgroundMessage', () => bgMessaging);
