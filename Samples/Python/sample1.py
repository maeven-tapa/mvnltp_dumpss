from PySide6.QtWidgets import QApplication, QGraphicsView, QGraphicsScene, QGraphicsRectItem, QGraphicsTextItem
from PySide6.QtGui import QPainter
from PySide6.QtCore import QRectF, Qt
import sys


class GraphicsViewSetRenderHint(QGraphicsView):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("PySide6")
        
        # Create scene
        self.scene = QGraphicsScene()
        
        self.status_text = self.scene.addText("Render Hint: Antialiasing DISABLED")
        self.status_text.setPos(20, 0) 
        
        # Add ellipse
        self.scene.addEllipse(20, 20, 200, 200)  
        self.setScene(self.scene)
        
        # Initial state of antialiasing
        self.antialiasing_enabled = True
        self.updateRenderHint()
        
        # Set up the view size

        self.show()
    
    def updateRenderHint(self):
        if self.antialiasing_enabled:
            self.setRenderHint(QPainter.Antialiasing, True)
            self.status_text.setPlainText("Render Hint: Antialiasing ENABLED")
        else:
            self.setRenderHint(QPainter.Antialiasing, False)
            self.status_text.setPlainText("Render Hint: Antialiasing DISABLED")
    
    def toggleAntialiasing(self):
        self.antialiasing_enabled = not self.antialiasing_enabled
        self.updateRenderHint()

class GraphicsViewSetTransformationAnchor(QGraphicsView):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("GraphicsViewSetTransformationAnchor Example")
        self.setGeometry(250, 250, 400, 400)
        scene = QGraphicsScene()
        scene.addText("Anchor Under Mouse")
        self.setScene(scene)
        self.setTransformationAnchor(QGraphicsView.AnchorUnderMouse)
        print("Transformation anchor set to AnchorUnderMouse.")
        self.show()

class GraphicsViewSetDragMode(QGraphicsView):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("GraphicsViewSetDragMode Example")
        self.setGeometry(300, 300, 400, 400)
        scene = QGraphicsScene()
        scene.addRect(30, 30, 100, 100)
        self.setScene(scene)
        self.setDragMode(QGraphicsView.ScrollHandDrag)
        print("Drag Mode set to ScrollHandDrag.")
        self.show()

class GraphicsViewFitInView(QGraphicsView):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("GraphicsViewFitInView Example")
        self.setGeometry(350, 350, 400, 400)
        scene = QGraphicsScene()
        rect_item = QGraphicsRectItem(0, 0, 200, 200)
        scene.addItem(rect_item)
        self.setScene(scene)
        self.fitInView(QRectF(0, 0, 200, 200), Qt.KeepAspectRatio)
        print("View fitted to scene with KeepAspectRatio.")
        self.show()

class GraphicsViewSetInteractive(QGraphicsView):
    def __init__(self, enable_interaction):
        super().__init__()
        self.setWindowTitle("GraphicsViewSetInteractive Example")
        self.setGeometry(400, 400, 400, 400)
        scene = QGraphicsScene()
        text_item = QGraphicsTextItem("Drag and interact with the rectangle!")
        text_item.setPos(10, 10)
        rect_item = QGraphicsRectItem(50, 50, 100, 100)
        rect_item.setFlag(QGraphicsRectItem.ItemIsMovable, enable_interaction)
        rect_item.setFlag(QGraphicsRectItem.ItemIsSelectable, enable_interaction)
        scene.addItem(text_item)
        scene.addItem(rect_item)
        self.setScene(scene)
        self.setInteractive(enable_interaction)
        if enable_interaction:
            print("Interaction enabled: You can drag and select the rectangle.")
        else:
            print("Interaction disabled: The rectangle is fixed.")
        self.show()

class GraphicsViewCenterOn(QGraphicsView):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("GraphicsViewCenterOn Example")
        self.setGeometry(450, 450, 400, 400)
        scene = QGraphicsScene()
        rect_item = QGraphicsRectItem(50, 50, 100, 100)
        scene.addItem(rect_item)
        self.setScene(scene)
        self.centerOn(rect_item)
        print("View centered on the rectangle item.")
        self.show()

if __name__ == "__main__":
    app = QApplication(sys.argv)
    
    main_window = GraphicsViewSetInteractive()
    
    sys.exit(app.exec())
