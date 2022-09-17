import {api} from '../../services';
import {addSessionToken, updateUserProfile, removeSessionToken, removeUserProfile} from './actions';
import {Platform} from "react-native";
import { useAsyncStorage } from '@react-native-community/async-storage';

export const init = () => async (dispatch, getState) => {
    const { getItem } = useAsyncStorage('session');
    const value = await getItem();
    if (value) {
        const auth = JSON.parse(value);
        const { refreshToken, token, user } = auth.auth;
        await dispatch(addSessionToken({refreshToken, token}));
        await dispatch(updateUserProfile(user));
    }
};

export const registerDeviceToken = () => async (dispatch, getState) => {
    try {
        const {deviceToken} = getState().auth;
        const type = Platform.OS === "ios" ? "IOS" : "ANDROID";
        console.log("======>: device Token ", deviceToken)
        const {data} = await api.post('/v1/agent/devices', {
            device_token: deviceToken,
            type
        });
        console.log("======>: register device Token success", data)
    } catch (error) {
        console.log("======>: register device Token error", error)
    }
};

export const login = (auth) => async (dispatch, getState) => {
    const { refreshToken, token, user } = auth;
    await dispatch(addSessionToken({refreshToken, token}));
    await dispatch(updateUserProfile(user));
    const { setItem } = useAsyncStorage('session');
    await setItem(JSON.stringify({auth}));
};

export const logout = () => async (dispatch, getState) => {
    await dispatch(removeSessionToken());
    await dispatch(removeUserProfile());
    const { removeItem } = useAsyncStorage('session');
    await removeItem();
};