import React from "react";

export default class ErrorBoundary extends React.Component {
  constructor(props){ super(props); this.state = { hasError:false, error:null }; }
  static getDerivedStateFromError(error){ return { hasError:true, error }; }
  componentDidCatch(error, info){ console.error("ErrorBoundary:", error, info); }
  render(){
    if(this.state.hasError){
      return (
        <div className="container py-4">
          <div className="alert alert-danger">
            <strong>Algo ha fallado renderizando esta página.</strong>
            <pre className="mt-2 mb-0" style={{whiteSpace:'pre-wrap'}}>{String(this.state.error)}</pre>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}
