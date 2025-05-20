import cv2
import numpy as np
import mediapipe as mp

# face mesh
mp_face = mp.solutions.face_mesh
face_mesh = mp_face.FaceMesh(min_detection_confidence = 0.6, min_tracking_confidence = 0.5)

# reading video
cap = cv2.VideoCapture(0)
print('\nCapturing\n')

while cap.isOpened():

    ret, frame = cap.read()

    # converting to RGB and processing face mesh
    frame = cv2.cvtColor(cv2.flip(frame, 1), cv2.COLOR_BGR2RGB)
    results = face_mesh.process(frame)

    # converting to BGR
    frame = cv2.cvtColor(frame, cv2.COLOR_RGB2BGR)

    # frame shape
    img_h, img_w, img_c = frame.shape
    face_3d = []
    face_2d = []

    if results.multi_face_landmarks:
        for face_landmarks in results.multi_face_landmarks:
            for idx, lm in enumerate(face_landmarks.landmark):
                # eyes, the nose, the chin, and mouth
                if idx == 33 or idx == 263 or idx == 1 or idx == 61 or idx == 291 or idx == 199:

                    x, y = int(lm.x*img_w),  int(lm.y*img_h)

                    # get 2d & 3d coords
                    face_2d.append([x,y])
                    face_3d.append([x, y, lm.z])

            # converting to numpy
            face_2d = np.array(face_2d, dtype=np.float64)
            face_3d = np.array(face_3d, dtype=np.float64)

            # camera
            focal_length = img_w
            center = (img_w/2, img_h/2)
            # helps to project 3d points to 2d
            camera_matrix = np.array(
                [[focal_length, 0, center[0]],
                 [0, focal_length, center[1]],
                 [0,0,1]])
    
            dist_coeffs = np.zeros((4,1), dtype = np.float64) # Assuming no lens distortion
            
            # Perspective n point - solving for the rotation and translation that minimizes the reprojection error from 3D-2D
            (success, rotation_vector, translation_vector) = cv2.solvePnP(face_3d, face_2d, camera_matrix, dist_coeffs)
            rmat = cv2.Rodrigues(rotation_vector)[0] # rotation matrix

            # Get angles
            angles, mtxR, mtxQ, Qx, Qy, Qz = cv2.RQDecomp3x3(rmat)

            x_angle = angles[0] * 360
            y_angle = angles[1] * 360

            # See where the user's head tilting
            if y_angle < -15:
                text = "Facing Right" #L
            elif y_angle > 15:
                text = "Facing Left" #R
            elif x_angle < -7:
                text = "Facing Down" #D
            elif x_angle > 20:
                text = "Facing Up" #U
            else:
                text = "Straight face"
                print(x_angle, y_angle)

            
            # Add the text on the image
            cv2.putText(frame, text, (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)

    cv2.imshow('Head Pose Estimation', frame)

    # if escape is pressed, stop
    if cv2.waitKey(1) == 27:
        print('Exiting!\n')
        break

cap.release()
cv2.destroyAllWindows()
