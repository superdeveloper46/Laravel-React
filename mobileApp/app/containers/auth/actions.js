export const ADD_SESSION_TOKEN = 'ADD_SESSION_TOKEN';
export const REMOVE_SESSION_TOKEN = 'REMOVE_SESSION_TOKEN';
export const ADD_DEVICE_TOKEN = 'ADD_DEVICE_TOKEN';
export const UPDATE_USER_PROFILE = 'UPDATE_USER_PROFILE';
export const REMOVE_USER_PROFILE = 'REMOVE_USER_PROFILE';

export const addSessionToken = tokenData => ({
  type: ADD_SESSION_TOKEN,
  tokenData,
});

export const removeSessionToken = () => ({
  type: REMOVE_SESSION_TOKEN,
});

export const addDeviceToken = deviceToken => ({
    type: ADD_DEVICE_TOKEN,
    deviceToken,
});

export const updateUserProfile = profile => ({
    type: UPDATE_USER_PROFILE,
    profile,
});

export const removeUserProfile = () => ({
    type: REMOVE_USER_PROFILE,
});
